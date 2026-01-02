<?php

namespace App\Controllers\Manager;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\ManagerTeam;
use App\Models\ProjectActivityLog;
use App\Models\Projects;
use App\Models\Users;
use App\Services\EmailService;
use Throwable;

class ManagerProjectController extends ManagerController
{

    /**
     * Render projects page
     */
    public function renderProjectsPage(): void
    {
        $projectModel = new Projects();

        $data = [
            "header_title" => "My Projects",
            "statuses" => $projectModel->getStatuses(),
        ];

        // Get team members for assignment
        $teamModel = new ManagerTeam();
        $managerId = (int)Session::get('userId');
        $teamMembers = $teamModel->getTeamMembers($managerId, '', 100, 0);
        $data['teamMembers'] = $teamMembers['data'];

        $this->render("/manager/projects", $data);
    }

    /**
     * Get projects paginated (API) - Only manager's own projects
     */
    public function getProjectsPaginated(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $search = $_GET['search'] ?? '';
            $statusFilter = isset($_GET['status_id']) && $_GET['status_id'] !== '' ? (int)$_GET['status_id'] : null;

            // Sorting
            $allowedSortColumns = ['name', 'created_at', 'project_status_id', 'task_count'];
            ['sort_by' => $sort_by, 'sort_order' => $sort_order] = $this->getSortParams($allowedSortColumns, "created_at");

            if ($sort_by === 'task_count') {
                $sortColumn = "task_count {$sort_order}";
            } else {
                $sortColumn = "p.{$sort_by} {$sort_order}";
            }

            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $projectModel = new Projects();
            $response = $projectModel->getByManager($managerId, $search, $statusFilter, $sortColumn, $limit, $offset);
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
            $managerId = (int)Session::get('userId');
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();

            // Check if manager owns this project
            if (!$projectModel->isManager($projectId, $managerId)) {
                $this->failure("You don't have permission to view this project.", [], HTTP_FORBIDDEN);
            }

            $project = $projectModel->getById($projectId);

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
     * Create new project (API) - Manager is auto-assigned
     */
    public function createProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $statusId = (int)($_POST['status_id'] ?? 1);

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

            $projectModel = new Projects();
            // Manager creates project: created_by = manager, manager_id = manager
            $projectId = $projectModel->create($managerId, $managerId, $name, $description, $statusId);

            if ($projectId) {
                // Assign team members if provided
                $assignees = $_POST['assignees'] ?? [];
                if (!empty($assignees) && is_array($assignees)) {
                    $teamModel = new ManagerTeam();
                    foreach ($assignees as $userId) {
                        $userId = (int)$userId;
                        if ($teamModel->isInManagerTeam($managerId, $userId)) {
                            $projectModel->assignUser($projectId, $userId);
                        }
                    }
                }

                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $managerId, 'created', [
                    'name' => $name,
                    'description' => $description,
                    'status_id' => $statusId
                ]);

                $this->success("Project created successfully.", ['project_id' => $projectId], HTTP_CREATED);
            }

            $this->failure("Failed to create project. Please try again.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while creating project.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update project (API) - Manager can only update their own projects
     */
    public function updateProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                $this->failure("Invalid JSON payload.", [], HTTP_BAD_REQUEST);
            }

            $projectId = (int)($input['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();

            // Check if manager owns this project
            if (!$projectModel->isManager($projectId, $managerId)) {
                $this->failure("You don't have permission to edit this project.", [], HTTP_FORBIDDEN);
            }

            $project = $projectModel->getById($projectId);

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

            if (empty($fieldsToUpdate)) {
                $this->failure("No changes detected.", [], HTTP_BAD_REQUEST);
            }

            $updated = $projectModel->update($projectId, $fieldsToUpdate);

            if ($updated) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $managerId, 'updated', $changes);

                $this->success("Project updated successfully.");
            }

            $this->failure("Failed to update project.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while updating project.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete project (API) - Soft delete
     */
    public function deleteProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = (int)($input['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();

            // Check if manager owns this project
            if (!$projectModel->isManager($projectId, $managerId)) {
                $this->failure("You don't have permission to delete this project.", [], HTTP_FORBIDDEN);
            }

            $project = $projectModel->getById($projectId);

            if (!$project) {
                $this->failure("Project not found.", [], HTTP_NOT_FOUND);
            }

            $deleted = $projectModel->softDelete($projectId);

            if ($deleted) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $managerId, 'deleted', [
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
     * Get assigned employees for a project (paginated)
     */
    public function getProjectAssignees(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();

            // Check ownership
            if (!$projectModel->isManager($projectId, $managerId)) {
                $this->failure("You don't have permission to view this project.", [], HTTP_FORBIDDEN);
            }

            $search = $_GET['search'] ?? '';
            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $response = $projectModel->getAssignedUsersPaginated($projectId, $search, 'u.first_name ASC', $limit, $offset);
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Assignees fetched.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get assignable employees for a project (manager's team members not assigned)
     */
    public function getAssignableEmployees(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();

            // Check ownership
            if (!$projectModel->isManager($projectId, $managerId)) {
                $this->failure("You don't have permission to modify this project.", [], HTTP_FORBIDDEN);
            }

            $search = $_GET['search'] ?? '';
            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            // Get manager's team members
            $teamModel = new ManagerTeam();
            $teamMembers = $teamModel->getTeamMembers($managerId, $search, $limit, $offset);

            // Filter out already assigned users
            $assignedUsers = $projectModel->getAssignedUsers($projectId);
            $assignedUserIds = array_column($assignedUsers, 'id');

            $filteredMembers = array_filter($teamMembers['data'], function ($member) use ($assignedUserIds) {
                return !in_array($member['user_id'], $assignedUserIds);
            });

            $filteredMembers = array_values($filteredMembers);
            $structuredResponse = $this->paginatedResponse($filteredMembers, $page, $limit, count($filteredMembers));

            $this->success("Assignable employees fetched.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign user to project (API)
     */
    public function assignUserToProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $projectId = (int)($_POST['project_id'] ?? 0);
            $userId = (int)($_POST['user_id'] ?? 0);

            if ($projectId <= 0 || $userId <= 0) {
                $this->failure("Invalid project or user ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();

            // Check if manager owns this project
            if (!$projectModel->isManager($projectId, $managerId)) {
                $this->failure("You don't have permission to modify this project.", [], HTTP_FORBIDDEN);
            }

            // Check if user is in manager's team
            $teamModel = new ManagerTeam();
            if (!$teamModel->isInManagerTeam($managerId, $userId)) {
                $this->failure("This user is not in your team.", [], HTTP_BAD_REQUEST);
            }

            // Check if already assigned
            if ($projectModel->isUserAssigned($projectId, $userId)) {
                $this->failure("Employee is already assigned to this project.", [], HTTP_BAD_REQUEST);
            }

            $assigned = $projectModel->assignUser($projectId, $userId);

            if ($assigned) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $managerId, 'employee_assigned', [
                    'employee_id' => $userId
                ]);

                // Send project assignment email
                try {
                    $userModel = new Users();
                    $user = $userModel->getUserDetailsById($userId);
                    $manager = $userModel->getUserDetailsById($managerId);
                    $project = $projectModel->getById($projectId);

                    if ($user && $manager && $project) {
                        $emailService = new EmailService();
                        $emailService->sendTemplateMail(
                            $user[0]['email'],
                            $user[0]['first_name'] . ' ' . $user[0]['last_name'],
                            'New Project Assignment: ' . $project['name'],
                            'project_assigned',
                            [
                                'employeeName' => $user[0]['first_name'] . ' ' . $user[0]['last_name'],
                                'projectName' => $project['name'],
                                'managerName' => $manager[0]['first_name'] . ' ' . $manager[0]['last_name'],
                                'projectDescription' => $project['description'] ?? ''
                            ]
                        );
                    }
                } catch (Throwable $emailError) {
                    Logger::error($emailError);
                }

                $this->success("User assigned to project successfully.");
            }

            $this->failure("Failed to assign user.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove user from project (API)
     */
    public function removeUserFromProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $projectId = (int)($_POST['project_id'] ?? 0);
            $userId = (int)($_POST['user_id'] ?? 0);

            if ($projectId <= 0 || $userId <= 0) {
                $this->failure("Invalid project or user ID.", [], HTTP_BAD_REQUEST);
            }

            $projectModel = new Projects();

            // Check if manager owns this project
            if (!$projectModel->isManager($projectId, $managerId)) {
                $this->failure("You don't have permission to modify this project.", [], HTTP_FORBIDDEN);
            }

            // Get user and project details before removal
            $userModel = new Users();
            $user = $userModel->getUserDetailsById($userId);
            $project = $projectModel->getById($projectId);

            $removed = $projectModel->removeUser($projectId, $userId);

            if ($removed) {
                // Log activity
                $activityLog = new ProjectActivityLog();
                $activityLog->logActivity($projectId, null, $managerId, 'employee_removed', [
                    'employee_id' => $userId
                ]);

                // Send project removed email to employee
                if (!empty($user) && !empty($project)) {
                    try {
                        $emailService = new EmailService();
                        $emailService->sendTemplateMail(
                            $user[0]['email'],
                            $user[0]['first_name'] . ' ' . $user[0]['last_name'],
                            'Removed from Project: ' . $project['name'],
                            'project_removed',
                            [
                                'employeeName' => $user[0]['first_name'] . ' ' . $user[0]['last_name'],
                                'projectName' => $project['name']
                            ]
                        );
                    } catch (Throwable $emailError) {
                        Logger::error($emailError);
                    }
                }

                $this->success("User removed from project successfully.");
            }

            $this->failure("Failed to remove user.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
