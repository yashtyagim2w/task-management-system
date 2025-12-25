<?php
namespace App\Controllers\Admin;

use App\Helpers\Logger;
use App\Helpers\Session;
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
            $data = $userModel->getAllUserPaginated($search, $sortColumn, $roleFilter, $activeStatusFilter, $currentUserId, $limit, $offset);
            $structuredResponse = $this->paginatedResponse($data, $page, $limit, count($data));

            $this->success("Users fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching users.", [], HTTP_INTERNAL_SERVER_ERROR);
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
            } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                $message = "Invalid email format.";
            } elseif (strlen($userData['phone']) != 10 || !ctype_digit($userData['phone'])) {
                $message = "Phone number must be exactly 10 digits.";
            } elseif (strlen($userData['password']) < 6) {
                $message = "Password must be at least 6 characters long.";
            } elseif (strlen($userData['password']) > 32) {
                $message = "Password is too long.";
            } elseif ($userData['roleId'] < 2 || $userData['roleId'] > 3) {
                $message = "Please select a valid role.";
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
                $isUserCreated = $userModel->create($userData['firstName'], $userData['lastName'], $userData['email'], $userData['phone'], $hashed_password, $userData['roleId'], $currentUserId);
                if($isUserCreated){
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
}