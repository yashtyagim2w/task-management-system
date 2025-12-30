<?php

namespace App\Controllers\Admin;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\ManagerTeam;
use App\Models\ProjectActivityLog;
use App\Models\Projects;
use App\Models\Users;
use Throwable;

class AdminProjectController extends AdminController
{

    /**
     * Render all projects page
     */
    public function renderProjectsPage(): void
    {
        $projectModel = new Projects();
        $userModel = new Users();

        $data = [
            "header_title" => "All Projects",
            "statuses" => $projectModel->getStatuses(),
        ];

        // Get all managers for filter and assignment
        $data['managers'] = $userModel->getAllUserPaginated('', 'u.first_name ASC', 2, null, 0, 1000, 0)['data'];

        // Active status options
        $data['activeStatuses'] = [
            ['id' => 1, 'name' => 'Active'],
            ['id' => 0, 'name' => 'Deleted']
        ];

        $this->render("/admin/projects", $data);
    }

    /**
     * Get all projects paginated (API)
     */
    public function getProjectsPaginated(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $search = $_GET['search'] ?? '';
            $managerFilter = isset($_GET['manager_id']) && $_GET['manager_id'] !== '' ? (int)$_GET['manager_id'] : null;
            $statusFilter = isset($_GET['status_id']) && $_GET['status_id'] !== '' ? (int)$_GET['status_id'] : null;

            // Active filter: null=all, 1=active, 0=deleted
            $activeFilter = null;
            if (isset($_GET['active_status']) && $_GET['active_status'] !== '') {
                $activeFilter = (int)$_GET['active_status'] === 1;
            }

            // Sorting
            $allowedSortColumns = ['name', 'created_at', 'project_status_id', 'task_count'];
            ['sort_by' => $sort_by, 'sort_order' => $sort_order] = $this->getSortParams($allowedSortColumns, "created_at");

            // Handle task_count sorting (subquery alias)
            if ($sort_by === 'task_count') {
                $sortColumn = "task_count {$sort_order}";
            } else {
                $sortColumn = "p.{$sort_by} {$sort_order}";
            }

            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $projectModel = new Projects();
            $response = $projectModel->getAllPaginated(
                $search,
                $managerFilter,
                $statusFilter,
                $activeFilter,
                $sortColumn,
                $limit,
                $offset
            );
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Projects fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching projects.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get project details (API)
     */
    public function getProjectDetails(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();
            $project = $projectModel->getById($projectId, true); // Include deleted for admin

            if (!$project) {
                $this->failure("Project not found.", [], HTTP_NOT_FOUND);
            }

            $this->success("Project details fetched.", ['project' => $project]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create new project (API)
     */
    public function createProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $adminId = (int)Session::get('userId');
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $statusId = (int)($_POST['status_id'] ?? 1);
            $managerId = (int)($_POST['manager_id'] ?? 0);

            // Regex patterns for validation
            $nameRegex = '/^(?=.*[A-Za-z0-9])[A-Za-z0-9 _()\-]{3,128}$/';
            $descRegex = '/^[A-Za-z0-9 _.,:;\'"!?()\-\n\r]{0,1000}$/';

            // Validation
            if (empty($name)) {
                $this->failure("Project name is required.", [], HTTP_BAD_REQUEST);
            }
            if (!preg_match($nameRegex, $name)) {
                $this->failure("Invalid project name. Use only letters, numbers, spaces, underscores, parentheses, and hyphens (3-128 characters).", [], HTTP_BAD_REQUEST);
            }
            if (!empty($description) && !preg_match($descRegex, $description)) {
                $this->failure("Invalid description. Contains disallowed characters.", [], HTTP_BAD_REQUEST);
            }
            if ($statusId < 1 || $statusId > 4) {
                $this->failure("Invalid status selected.", [], HTTP_BAD_REQUEST);
            }
            if ($managerId <= 0) {
                $this->failure("Please select a manager for the project.", [], HTTP_BAD_REQUEST);
            }

            // Validate manager exists and is a manager
            $userModel = new Users();
            $manager = $userModel->getUserDetailsById($managerId);
            if (empty($manager) || $manager[0]['role_id'] != 2) {
                $this->failure("Invalid manager selected.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();
            $projectId = $projectModel->create($adminId, $managerId, $name, $description, $statusId);

            if ($projectId) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $adminId, 'created', [
                    'name' => $name,
                    'description' => $description,
                    'status_id' => $statusId,
                    'manager_id' => $managerId
                ]);

                $this->success("Project created successfully.", ['project_id' => $projectId], HTTP_CREATED);
            }

            $this->failure("Failed to create project.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while creating project.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update project (API) - Admin can update any project
     */
    public function updateProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $adminId = (int)Session::get('userId');
            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                $this->failure("Invalid JSON payload.", [], HTTP_BAD_REQUEST);
            }

            $projectId = (int)($input['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();
            $project = $projectModel->getById($projectId, true);

            if (!$project) {
                $this->failure("Project not found.", [], HTTP_NOT_FOUND);
            }

            $fieldsToUpdate = [];
            $changes = [];

            // Regex patterns for validation
            $nameRegex = '/^(?=.*[A-Za-z0-9])[A-Za-z0-9 _()\-]{3,128}$/';
            $descRegex = '/^[A-Za-z0-9 _.,:;\'"!?()\-\n\r]{0,1000}$/';

            // Name
            if (isset($input['name']) && !empty(trim($input['name']))) {
                $name = trim($input['name']);
                if (!preg_match($nameRegex, $name)) {
                    $this->failure("Invalid project name. Use only letters, numbers, spaces, underscores, parentheses, and hyphens (3-128 characters).", [], HTTP_BAD_REQUEST);
                }
                if ($name !== $project['name']) {
                    $fieldsToUpdate['name'] = $name;
                    $changes['name'] = ['old' => $project['name'], 'new' => $name];
                }
            }

            // Description
            if (isset($input['description'])) {
                $description = trim($input['description']);
                if (!empty($description) && !preg_match($descRegex, $description)) {
                    $this->failure("Invalid description. Contains disallowed characters.", [], HTTP_BAD_REQUEST);
                }
                if ($description !== $project['description']) {
                    $fieldsToUpdate['description'] = $description;
                    $changes['description'] = ['old' => $project['description'], 'new' => $description];
                }
            }

            // Status
            if (isset($input['status_id']) && $input['status_id'] >= 1 && $input['status_id'] <= 4) {
                $statusId = (int)$input['status_id'];
                if ($statusId !== (int)$project['project_status_id']) {
                    $fieldsToUpdate['project_status_id'] = $statusId;
                    $changes['status'] = ['old' => $project['status_name'], 'new_id' => $statusId];
                }
            }

            // Manager
            if (isset($input['manager_id']) && (int)$input['manager_id'] > 0) {
                $newManagerId = (int)$input['manager_id'];
                if ($newManagerId !== (int)$project['manager_id']) {
                    // Validate new manager
                    $userModel = new Users();
                    $manager = $userModel->getUserDetailsById($newManagerId);
                    if (!empty($manager) && $manager[0]['role_id'] == 2) {
                        $fieldsToUpdate['manager_id'] = $newManagerId;
                        $changes['manager'] = [
                            'old_id' => $project['manager_id'],
                            'old_name' => $project['manager_first_name'] . ' ' . $project['manager_last_name'],
                            'new_id' => $newManagerId
                        ];
                    }
                }
            }

            if (empty($fieldsToUpdate)) {
                $this->failure("No changes detected.", [], HTTP_BAD_REQUEST);
            }

            $updated = $projectModel->update($projectId, $fieldsToUpdate);

            if ($updated) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $adminId, 'updated', $changes);

                $this->success("Project updated successfully.");
            }

            $this->failure("Failed to update project.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while updating project.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete project (API) - Admin can delete any project (soft delete)
     */
    public function deleteProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $adminId = (int)Session::get('userId');
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = (int)($input['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();
            $project = $projectModel->getById($projectId);

            if (!$project) {
                $this->failure("Project not found.", [], HTTP_NOT_FOUND);
            }

            $deleted = $projectModel->softDelete($projectId);

            if ($deleted) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $adminId, 'deleted', [
                    'project_name' => $project['name']
                ]);

                $this->success("Project deleted successfully.");
            }

            $this->failure("Failed to delete project.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while deleting project.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Recover deleted project (API) - Admin only
     */
    public function recoverProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $adminId = (int)Session::get('userId');
            $projectId = (int)($_POST['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();
            $project = $projectModel->getById($projectId, true); // Include deleted

            if (!$project) {
                $this->failure("Project not found.", [], HTTP_NOT_FOUND);
            }

            if ($project['is_deleted'] == 0) {
                $this->failure("Project is not deleted.", [], HTTP_BAD_REQUEST);
            }

            // Recover by setting is_deleted = 0
            $recovered = $projectModel->update($projectId, ['is_deleted' => 0]);

            if ($recovered) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $adminId, 'recovered', [
                    'project_name' => $project['name']
                ]);

                $this->success("Project recovered successfully.");
            }

            $this->failure("Failed to recover project.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while recovering project.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get assigned employees for a project (paginated, for modal)
     */
    public function getProjectAssignees(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $search = $_GET['search'] ?? '';
            $allowedSortColumns = ['first_name', 'last_name', 'email', 'assigned_at'];
            ['sort_by' => $sort_by, 'sort_order' => $sort_order] = $this->getSortParams($allowedSortColumns, "first_name");
            $sortColumn = $sort_by === 'assigned_at' ? "pua.created_at {$sort_order}" : "u.{$sort_by} {$sort_order}";

            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $projectModel = new Projects();
            $response = $projectModel->getAssignedUsersPaginated($projectId, $search, $sortColumn, $limit, $offset);
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Assignees fetched.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get assignable employees for a project (manager's team members, paginated)
     */
    public function getAssignableEmployees(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            // Get project's manager
            $projectModel = new Projects();
            $managerId = $projectModel->getProjectManagerId($projectId);

            if (!$managerId) {
                $this->failure("Project not found or has no manager.", [], HTTP_NOT_FOUND);
            }

            $search = $_GET['search'] ?? '';
            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            // Get manager's team members (not already assigned to project)
            $teamModel = new ManagerTeam();
            $teamMembers = $teamModel->getTeamMembers($managerId, $search, $limit, $offset);

            // Filter out already assigned users
            $assignedUsers = $projectModel->getAssignedUsers($projectId);
            $assignedUserIds = array_column($assignedUsers, 'id');

            $filteredMembers = array_filter($teamMembers['data'], function ($member) use ($assignedUserIds) {
                return !in_array($member['user_id'], $assignedUserIds);
            });

            // Re-index array
            $filteredMembers = array_values($filteredMembers);

            $structuredResponse = $this->paginatedResponse($filteredMembers, $page, $limit, count($filteredMembers));

            $this->success("Assignable employees fetched.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign employee to project (API)
     */
    public function assignUserToProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $adminId = (int)Session::get('userId');
            $projectId = (int)($_POST['project_id'] ?? 0);
            $userId = (int)($_POST['user_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }
            if ($userId <= 0) {
                $this->failure("Invalid user ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();
            $project = $projectModel->getById($projectId);

            if (!$project) {
                $this->failure("Project not found.", [], HTTP_NOT_FOUND);
            }

            // Validate user exists and is an employee
            $userModel = new Users();
            $user = $userModel->getUserDetailsById($userId);
            if (empty($user) || $user[0]['role_id'] != 3) {
                $this->failure("Invalid employee selected.", [], HTTP_BAD_REQUEST);
            }

            // Check if user is in manager's team
            $teamModel = new ManagerTeam();
            $managerId = $project['manager_id'];
            if (!$teamModel->isInManagerTeam($managerId, $userId)) {
                $this->failure("This employee is not in the project manager's team.", [], HTTP_BAD_REQUEST);
            }

            // Check if already assigned
            if ($projectModel->isUserAssigned($projectId, $userId)) {
                $this->failure("Employee is already assigned to this project.", [], HTTP_BAD_REQUEST);
            }

            $assigned = $projectModel->assignUser($projectId, $userId);

            if ($assigned) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $adminId, 'employee_assigned', [
                    'employee_id' => $userId,
                    'employee_name' => $user[0]['first_name'] . ' ' . ($user[0]['last_name'] ?? '')
                ]);

                $this->success("Employee assigned to project successfully.");
            }

            $this->failure("Failed to assign employee.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove employee from project (API)
     */
    public function removeUserFromProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $adminId = (int)Session::get('userId');
            $projectId = (int)($_POST['project_id'] ?? 0);
            $userId = (int)($_POST['user_id'] ?? 0);

            if ($projectId <= 0 || $userId <= 0) {
                $this->failure("Invalid project or user ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();
            $project = $projectModel->getById($projectId);

            if (!$project) {
                $this->failure("Project not found.", [], HTTP_NOT_FOUND);
            }

            // Get user details for logging
            $userModel = new Users();
            $user = $userModel->getUserDetailsById($userId);

            $removed = $projectModel->removeUser($projectId, $userId);

            if ($removed) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $adminId, 'employee_removed', [
                    'employee_id' => $userId,
                    'employee_name' => !empty($user) ? $user[0]['first_name'] . ' ' . ($user[0]['last_name'] ?? '') : 'Unknown'
                ]);

                $this->success("Employee removed from project successfully.");
            }

            $this->failure("Failed to remove employee.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
