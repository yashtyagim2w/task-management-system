<?php

namespace App\Controllers\Employee;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\Projects;
use App\Models\ProjectTasks;
use App\Models\TaskComments;
use App\Models\TaskActivityLog;
use Throwable;

class EmployeeTaskController extends EmployeeController
{
    /**
     * Render tasks page with project selector
     */
    public function renderTasksPage(): void
    {
        $taskModel = new ProjectTasks();
        $projectModel = new Projects();
        $employeeId = (int)Session::get('userId');

        $data = [
            "header_title" => "My Tasks - Kanban",
            "statuses" => $taskModel->getStatuses(),
            "priorities" => $taskModel->getPriorities(),
            // Only projects the employee is assigned to
            "projects" => $projectModel->getByAssignedUser($employeeId, '', null, 'p.name ASC', 100, 0)['data'],
        ];

        $this->render("/employee/tasks", $data);
    }

    /**
     * Get tasks for Kanban board (API) - Only assigned tasks
     */
    public function getTasksForKanban(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $employeeId = (int)Session::get('userId');
            $projectId = (int)($_GET['project_id'] ?? 0);
            $priorityId = isset($_GET['priority_id']) ? (int)$_GET['priority_id'] : null;

            if ($projectId <= 0) {
                $this->failure("Please select a project.", [], HTTP_BAD_REQUEST);
            }

            // Verify employee is assigned to this project
            $projectModel = new Projects();
            if (!$projectModel->isUserAssigned($projectId, $employeeId)) {
                $this->failure("Access denied to this project.", [], HTTP_FORBIDDEN);
            }

            $taskModel = new ProjectTasks();
            // Only get tasks assigned to this employee (employee ID used as assignee filter)
            $tasks = $taskModel->getForKanban($projectId, (string)$employeeId, $priorityId);
            $canDragDrop = $taskModel->isDragDropAllowed($projectId);

            $this->success("Tasks fetched.", [
                'tasks' => $tasks,
                'can_drag_drop' => $canDragDrop
            ]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get task details for modal (API)
     */
    public function getTaskDetails(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $employeeId = (int)Session::get('userId');
            $taskId = (int)($_GET['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }

            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            // Verify employee is assigned to this project
            $projectModel = new Projects();
            if (!$projectModel->isUserAssigned($task['project_id'], $employeeId)) {
                $this->failure("Access denied.", [], HTTP_FORBIDDEN);
            }

            $this->success("Task details fetched.", [
                'task' => $task,
                'assignees' => [] // Employees can't reassign
            ]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update task status (drag-drop only)
     */
    public function updateTaskStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $employeeId = (int)Session::get('userId');
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = (int)($input['task_id'] ?? 0);
            $statusId = (int)($input['status_id'] ?? 0);

            if ($taskId <= 0 || $statusId < 1 || $statusId > 4) {
                $this->failure("Invalid parameters.", [], HTTP_BAD_REQUEST);
            }

            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            // Verify employee is assigned to this task
            if ((int)$task['assigned_to'] !== $employeeId) {
                $this->failure("You can only update tasks assigned to you.", [], HTTP_FORBIDDEN);
            }

            // Check if drag-drop is allowed
            if (!$taskModel->isDragDropAllowed($task['project_id'])) {
                $this->failure("Project must be in progress.", [], HTTP_FORBIDDEN);
            }

            $oldStatusId = $task['task_status_id'];
            $updated = $taskModel->updateStatus($taskId, $statusId);

            if ($updated) {
                $activityLog = new TaskActivityLog();
                $activityLog->logActivity($task['project_id'], $taskId, $employeeId, 'status_changed', [
                    'old_status_id' => $oldStatusId,
                    'new_status_id' => $statusId
                ]);

                $this->success("Status updated.");
            }

            $this->failure("Failed to update.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get task comments (API)
     */
    public function getTaskComments(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $employeeId = (int)Session::get('userId');
            $taskId = (int)($_GET['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }

            // Verify access
            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            $projectModel = new Projects();
            if (!$projectModel->isUserAssigned($task['project_id'], $employeeId)) {
                $this->failure("Access denied.", [], HTTP_FORBIDDEN);
            }

            $commentModel = new TaskComments();
            $comments = $commentModel->getByTask($taskId);

            $this->success("Comments fetched.", ['comments' => $comments]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add comment to task (API)
     */
    public function addTaskComment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $employeeId = (int)Session::get('userId');
            $taskId = (int)($_POST['task_id'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }
            if (empty($comment)) {
                $this->failure("Comment cannot be empty.", [], HTTP_BAD_REQUEST);
            }

            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            // Verify employee is assigned to this project
            $projectModel = new Projects();
            if (!$projectModel->isUserAssigned($task['project_id'], $employeeId)) {
                $this->failure("Access denied.", [], HTTP_FORBIDDEN);
            }

            $commentModel = new TaskComments();
            $commentId = $commentModel->create($task['project_id'], $employeeId, $comment, $taskId);

            if ($commentId) {
                $activityLog = new TaskActivityLog();
                $activityLog->logActivity($task['project_id'], $taskId, $employeeId, 'comment_added', ['comment_id' => $commentId]);

                $comments = $commentModel->getByTask($taskId);
                $newComment = array_pop($comments);

                $this->success("Comment added.", ['comment' => $newComment], HTTP_CREATED);
            }

            $this->failure("Failed to add comment.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get task activity logs (API)
     */
    public function getTaskActivityLogs(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $employeeId = (int)Session::get('userId');
            $taskId = (int)($_GET['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }

            // Verify access
            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            $projectModel = new Projects();
            if (!$projectModel->isUserAssigned($task['project_id'], $employeeId)) {
                $this->failure("Access denied.", [], HTTP_FORBIDDEN);
            }

            $activityLog = new TaskActivityLog();
            $logs = $activityLog->getTaskLogs($taskId, 100, 0);

            $this->success("Activity logs fetched.", $logs);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get tasks paginated (legacy fallback)
     */
    public function getTasksPaginated(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $employeeId = (int)Session::get('userId');
            $search = $_GET['search'] ?? '';
            $statusFilter = isset($_GET['status_id']) && $_GET['status_id'] !== '' ? (int)$_GET['status_id'] : null;
            $priorityFilter = isset($_GET['priority_id']) && $_GET['priority_id'] !== '' ? (int)$_GET['priority_id'] : null;

            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $taskModel = new ProjectTasks();
            $response = $taskModel->getByAssignedUser($employeeId, $search, $statusFilter, $priorityFilter, $limit, $offset);
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Tasks fetched.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
