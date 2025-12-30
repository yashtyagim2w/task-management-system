<?php
namespace App\Controllers\Admin;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Helpers\Validation;
use App\Models\ManagerTeam;
use App\Models\Users;
use Throwable;

class AdminUserController extends AdminController {

    // Render Users Management Page
    public function renderAllUsers(): void {

        $data = [
            "header_title" => "Manage Users",
        ];
        $roles = [
            ['id' => 2, 'name' => 'Manager'],
            ['id' => 3, 'name' => 'Employee']
        ];
        $activeStatus = [
            ['id' => 1, 'name' => 'Active'],
            ['id' => 0, 'name' => 'Inactive']
        ];
        $data['roles'] = $roles;
        $data['activeStatus'] = $activeStatus;
        $this->render("/admin/users", $data);
    }

    // Get Users Paginated (API)
    public function getUsersPaginated() {
        if($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }
        try {
            $allowedSortColumns = ['first_name', 'last_name', 'email', 'is_active', 'created_at'];
            ['sort_by' => $sort_by, 'sort_order' => $sort_order] = $this->getSortParams($allowedSortColumns, "u.created_at");
            $sortColumn = "{$sort_by} {$sort_order}";

            $roleFilter = isset($_GET['role_id']) && $_GET['role_id'] !== '' ? (int)$_GET['role_id'] : null;
            $activeStatusFilter = isset($_GET['active_status_id']) && $_GET['active_status_id'] !== '' ? (int)$_GET['active_status_id'] : null;
            
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();
            $currentUserId = (int)Session::get('userId');
            
            $userModel = new Users();
            $response = $userModel->getAllUserPaginated($search, $sortColumn, $roleFilter, $activeStatusFilter, $currentUserId, $limit, $offset);
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Users fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching users.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllManagers() {
        if($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }
        try {
            $userModel = new Users();
            $response = $userModel->getAllManagers();
            $this->success("Managers fetched successfully.", $response);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching managers.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Create New User (API)
    public function createNewUser() {
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $userData = [
                'firstName' => htmlspecialchars(trim($_POST['first_name'] ?? '')),
                'lastName' => htmlspecialchars(trim($_POST['last_name'] ?? '')),
                'email' => htmlspecialchars(trim($_POST['email'] ?? '')),
                'phone' => $_POST['phone'] ?? '',
                'password' => $_POST['password'] ?? '',
                'roleId' => (int) ($_POST['role_id'] ?? 0),
                'managerId' => (int) ($_POST['manager_id'] ?? 0),
            ];

            // Validation
            if(empty($userData['firstName']) || empty($userData['email']) || empty($userData['phone']) || empty($userData['password'])) {
                $message = "Please fill in all required fields.";
            } elseif (strlen($userData['firstName']) < 2) {
                $message = "First name must be at least 2 characters long.";
            } elseif (strlen($userData['firstName']) > 255) {
                $message = "First name is too long.";
            } elseif (!preg_match("/^[a-zA-Z ]+$/", $userData['firstName'])) {
                $message = "First name must only contain alphabets.";
            } elseif (!empty($userData['lastName']) && !preg_match("/^[a-zA-Z ]+$/", $userData['lastName'])) {
                $message = "Last name must only contain alphabets.";
            } elseif (strlen($userData['lastName']) > 255) {
                $message = "Last name is too long.";
            } elseif (!Validation::isValidEmail($userData['email'])) {
                $message = "Invalid email format.";
            } elseif (strlen($userData['phone']) != 10 || !ctype_digit($userData['phone'])) {
                $message = "Phone number must be exactly 10 digits.";
            } elseif (strlen($userData['password']) < 6) {
                $message = "Password must be at least 6 characters long.";
            } elseif (strlen($userData['password']) > 32) {
                $message = "Password is too long.";
            } elseif ($userData['roleId'] < 2 || $userData['roleId'] > 3) {
                $message = "Please select a valid role.";
            } else if($userData['roleId'] == 2 && $userData['managerId']) {
                $message = "Manager cannot be assigned.";
            } else if ($userData['roleId'] == 3 && empty($userData['managerId'])) {
                $message = "Please select a manager.";
            } else {    
                $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);

                // check if user already exists
                $userModel = new Users();
                $userExist = $userModel->existByEmailOrPhone($userData['email'], $userData['phone']);
                
                if($userExist){
                    $message = "User with this email or phone number already exists.";
                    $this->failure($message, [], HTTP_BAD_REQUEST);
                }

                $currentUserId = Session::get('userId');
                // create user
                $newUserId = $userModel->create($userData['firstName'], $userData['lastName'], $userData['email'], $userData['phone'], $hashed_password, $userData['roleId'], $currentUserId);
                if($newUserId){
                    if($userData['roleId'] == 3){
                        $managerTeamModel = new ManagerTeam();
                        $managerTeamModel->addMember($userData['managerId'], $newUserId);
                    }
                    $message = "User registered successfully!";
                    $this->success($message, [], HTTP_CREATED);
                }
                    
                $message = "Failed to create user. Please try again.";
            }

            $this->failure($message, [], HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while creating the user.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Update User Details (API)
    public function updateUserDetails() {
        if($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        $currentUserId = (int)Session::get('userId');

        try {
            // Read JSON body
            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                $this->failure("Invalid JSON payload.", [], HTTP_BAD_REQUEST);
            }

            // Extract & sanitize
            $userId = (int) ($input['user_id'] ?? 0);
            $firstName = htmlspecialchars(trim($input['first_name'] ?? ''));
            $lastName = htmlspecialchars(trim($input['last_name'] ?? ''));
            $email = htmlspecialchars(trim($input['email'] ?? ''));
            $phone = htmlspecialchars(trim($input['phone'] ?? ''));
            $password = $input['password'] ?? '';
            $roleId = (int) ($input['role_id'] ?? 0);
            $isActive = (int) ($input['is_active'] ?? -1);
            $managerId = (int) ($input['manager_id'] ?? 0);

            $message = "";
        
            // Validation
            if ($userId <= 0) {
                $message = "Invalid user ID.";
            } else if ($userId === $currentUserId) {
                $message = "You cannot update your own profile.";
            } else if ($firstName === '' || strlen($firstName) < 2 || strlen($firstName) > 128) {
                $message = "First name must be between 2 and 128 characters.";
            } else if ($lastName !== '' && strlen($lastName) > 128) {
                $message = "Last name must be less than 128 characters.";
            } else if (!Validation::isValidEmail($email)) {
                $message = "Invalid email address.";
            } else if (strlen($email) > 128) {
                $message = "Email address is too long.";
            } else if (!ctype_digit($phone) || strlen($phone) !== 10) {
                $message = "Phone number must be 10 digits.";
            } elseif ($password !== '' && (strlen($password) < 6 || strlen($password) > 32)) {
                $message = "Password must be between 6 and 32 characters.";
            } else if (!in_array($roleId, [2, 3], true)) {
                $message = "Invalid role selected.";
            } else if (!in_array($isActive, [0, 1], true)) {
                $message = "Invalid active status.";
            } else if($roleId == 2 && $managerId) {
                $message = "Manager cannot be assigned.";
            } else if ($roleId == 3 && empty($managerId)) {
                $message = "Please select a manager.";
            } else if($userId === $managerId) {
                $message = "Manager id and user id cannot be same.";
            }
            
            if ($message !== "") {
                $this->failure($message, [], HTTP_BAD_REQUEST);
            }
            
            // Get current manager of the employee (if exists)
            $managerTeamModel = new ManagerTeam();
            $currentManagerData = $managerTeamModel->getEmployeeManager($userId);
            $currentManagerId = $currentManagerData['id'] ?? null;
        
            $userModel = new Users();
            $userDetails = $userModel->getUserDetailsById($userId);
            
            if(!$userDetails) {
                $this->failure("User doesn't exist.", [], HTTP_BAD_REQUEST);
            }
            
            // Only validate manager if role is Employee
            if($roleId == 3) {
                $managerDetails = $userModel->getUserDetailsById($managerId);
                if(!$managerDetails) {
                    $this->failure("Manager doesn't exist.", [], HTTP_BAD_REQUEST);
                }
            }
            
            $userDetails = $userDetails[0];

            $fieldToUpdate = [];
            
            if ($firstName !== '' && $firstName !== $userDetails['first_name']) {
                $fieldToUpdate['first_name'] = $firstName;
            }
            if ($lastName !== '' && $lastName !== $userDetails['last_name']) {
                $fieldToUpdate['last_name'] = $lastName;
            }
            if ($email !== '' && $email !== $userDetails['email']) {
                $fieldToUpdate['email'] = $email;
            }
            if ($phone !== '' && $phone !== $userDetails['phone_number']) {
                $fieldToUpdate['phone_number'] = $phone;
            }
            if ($password !== '' && !password_verify($password, $userDetails['password'])) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $fieldToUpdate['password'] = $hashedPassword;
            }
            if (in_array($roleId, [2, 3], true) && $roleId !== $userDetails['role_id']) {
                $fieldToUpdate['role_id'] = $roleId;
            }
            if (in_array($isActive, [0, 1], true) && $isActive !== $userDetails['is_active']) {
                $fieldToUpdate['is_active'] = $isActive;
            }
            
            // Check if manager needs to be updated
            $managerToUpdate = false;
            if ($roleId == 3 && $managerId !== $currentManagerId) {
                $managerToUpdate = true;
            }
            
            // If role changed from Employee (3) to Manager (2), remove from team
            $removeFromTeam = false;
            if ($userDetails['role_id'] == 3 && $roleId == 2 && $currentManagerId !== null) {
                $removeFromTeam = true;
            }

            if (count($fieldToUpdate) === 0 && !$managerToUpdate && !$removeFromTeam) {
                $this->failure("No valid fields to update.", [], HTTP_BAD_REQUEST);
            }

            // Update user details if there are fields to update
            if (count($fieldToUpdate) > 0) {
                $updated = $userModel->update($userId, $fieldToUpdate);
                if (!$updated) {
                    $this->failure("Failed to update user.", [], HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Update manager assignment if needed
            if($managerToUpdate) {
                $managerTeamModel->adminAssignEmployee($managerId, $userId);
            }
            
            // Remove from team if role changed from Employee to Manager
            if($removeFromTeam) {
                $managerTeamModel->removeMember($currentManagerId, $userId);
            }

            $this->success("User updated successfully.");

        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while updating the user.", [], HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}