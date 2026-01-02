<?php

namespace App\Controllers\Admin;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\Projects;
use App\Models\ProjectTasks;
use App\Models\TaskComments;
use App\Models\TaskActivityLog;
use App\Models\Users;
use App\Services\EmailService;
use Throwable;

class AdminTaskController extends AdminController
{
    /**
     * Render tasks page with project selector
     */
    public function renderTasksPage(): void
    {
        $taskModel = new ProjectTasks();
        $projectModel = new Projects();

        $data = [
            "header_title" => "Tasks - Kanban",
            "statuses" => $taskModel->getStatuses(),
            "priorities" => $taskModel->getPriorities(),
            "projects" => $projectModel->getAllPaginated('', null, null, true, 'p.name ASC', 100, 0)['data'],
        ];

        $this->render("/admin/tasks", $data);
    }

    /**
     * Get tasks for Kanban board (API)
     */
    public function getTasksForKanban(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Please select a project.", [], HTTP_BAD_REQUEST);
            }

            $taskModel = new ProjectTasks();
            $tasks = $taskModel->getForKanban($projectId);
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
            $taskId = (int)($_GET['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }

            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            // Get assignable employees for this project
            $userModel = new Users();
            $projectModel = new Projects();
            $assignees = $projectModel->getProjectAssignees($task['project_id']);

            $this->success("Task details fetched.", [
                'task' => $task,
                'assignees' => $assignees
            ]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create new task (API)
     */
    public function createTask(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $adminId = (int)Session::get('userId');
            $projectId = (int)($_POST['project_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priorityId = (int)($_POST['priority_id'] ?? 2);
            $statusId = (int)($_POST['status_id'] ?? 1);
            $dueDate = $_POST['due_date'] ?? null;
            $assignedTo = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;

            // Validation
            if ($projectId <= 0) {
                $this->failure("Please select a project.", [], HTTP_BAD_REQUEST);
            }

            // Verify project exists
            $projectModel = new Projects();
            $project = $projectModel->getById($projectId, true);
            if (!$project) {
                $this->failure("Invalid project.", [], HTTP_BAD_REQUEST);
            }

            // Name validation (regex)
            $nameRegex = '/^(?=.*[A-Za-z0-9])[A-Za-z0-9 _()\-]{3,255}$/';
            if (!preg_match($nameRegex, $name)) {
                $this->failure("Invalid task name. 3-255 chars, letters, numbers, spaces, -, _, () only.", [], HTTP_BAD_REQUEST);
            }

            if ($priorityId < 1 || $priorityId > 3) {
                $this->failure("Invalid priority.", [], HTTP_BAD_REQUEST);
            }
            if ($statusId < 1 || $statusId > 4) {
                $this->failure("Invalid status.", [], HTTP_BAD_REQUEST);
            }

            // Due date validation
            if ($dueDate !== null && $dueDate !== '') {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dueDate);
                $today = new \DateTime('today');
                if (!$dateObj || $dateObj < $today) {
                    $this->failure("Due date cannot be in the past.", [], HTTP_BAD_REQUEST);
                }
            } else {
                $dueDate = null;
            }

            $taskModel = new ProjectTasks();
            $taskId = $taskModel->create($projectId, $adminId, $name, $description, $priorityId, $statusId, $dueDate, $assignedTo);

            if ($taskId) {
                // Log activity
                $activityLog = new TaskActivityLog();
                $activityLog->logActivity($projectId, $taskId, $adminId, 'created', [
                    'task_name' => $name,
                    'priority_id' => $priorityId,
                    'status_id' => $statusId,
                    'due_date' => $dueDate,
                    'assigned_to' => $assignedTo
                ]);

                if ($assignedTo) {
                    $activityLog->logActivity($projectId, $taskId, $adminId, 'assigned', ['assigned_to' => $assignedTo]);

                    // Send task assignment email
                    try {
                        $userModel = new Users();
                        $assignee = $userModel->getUserDetailsById($assignedTo);
                        $assigner = $userModel->getUserDetailsById($adminId);
                        if ($assignee && $assigner) {
                            $priorities = ['1' => 'low', '2' => 'medium', '3' => 'high'];
                            $emailService = new EmailService();
                            $emailService->sendTemplateMail(
                                $assignee[0]['email'],
                                $assignee[0]['first_name'] . ' ' . $assignee[0]['last_name'],
                                'New Task Assigned: ' . $name,
                                'task_assigned',
                                [
                                    'assigneeName' => $assignee[0]['first_name'] . ' ' . $assignee[0]['last_name'],
                                    'assignerName' => $assigner[0]['first_name'] . ' ' . $assigner[0]['last_name'],
                                    'taskName' => $name,
                                    'projectName' => $project['name'],
                                    'priority' => $priorities[$priorityId] ?? 'medium',
                                    'dueDate' => $dueDate ? date('M d, Y', strtotime($dueDate)) : '',
                                    'description' => $description
                                ]
                            );
                        }
                    } catch (Throwable $emailError) {
                        Logger::error($emailError);
                    }
                }

                $this->success("Task created successfully.", ['task_id' => $taskId], HTTP_CREATED);
            }

            $this->failure("Failed to create task.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update task status (drag-drop)
     */
    public function updateTaskStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
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

            // Check if drag-drop is allowed
            if (!$taskModel->isDragDropAllowed($task['project_id'])) {
                $this->failure("Cannot change status. Project must be in progress.", [], HTTP_FORBIDDEN);
            }

            $oldStatusId = $task['task_status_id'];
            $updated = $taskModel->updateStatus($taskId, $statusId);

            if ($updated) {
                // Log activity
                $userId = (int)Session::get('userId');
                $activityLog = new TaskActivityLog();
                $activityLog->logActivity($task['project_id'], $taskId, $userId, 'status_changed', [
                    'old_status_id' => $oldStatusId,
                    'new_status_id' => $statusId
                ]);

                $this->success("Status updated.");
            }

            $this->failure("Failed to update status.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update task (API)
     */
    public function updateTask(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = (int)($input['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }

            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            $fieldsToUpdate = [];
            $changes = [];

            // Name validation
            if (isset($input['name']) && !empty(trim($input['name']))) {
                $name = trim($input['name']);
                $nameRegex = '/^(?=.*[A-Za-z0-9])[A-Za-z0-9 _()\-]{3,255}$/';
                if (!preg_match($nameRegex, $name)) {
                    $this->failure("Invalid task name.", [], HTTP_BAD_REQUEST);
                }
                if ($name !== $task['name']) {
                    $fieldsToUpdate['name'] = $name;
                    $changes['name'] = ['old' => $task['name'], 'new' => $name];
                }
            }

            if (isset($input['description'])) {
                $description = trim($input['description']);
                if ($description !== $task['description']) {
                    $fieldsToUpdate['description'] = $description;
                    $changes['description'] = ['old' => $task['description'], 'new' => $description];
                }
            }

            if (isset($input['status_id']) && $input['status_id'] >= 1 && $input['status_id'] <= 4) {
                $statusId = (int)$input['status_id'];
                if ($statusId !== (int)$task['task_status_id']) {
                    $fieldsToUpdate['task_status_id'] = $statusId;
                    $changes['status'] = ['old_id' => $task['task_status_id'], 'new_id' => $statusId];
                }
            }

            if (isset($input['priority_id']) && $input['priority_id'] >= 1 && $input['priority_id'] <= 3) {
                $priorityId = (int)$input['priority_id'];
                if ($priorityId !== (int)$task['task_priority_id']) {
                    $fieldsToUpdate['task_priority_id'] = $priorityId;
                    $changes['priority'] = ['old_id' => $task['task_priority_id'], 'new_id' => $priorityId];
                }
            }

            if (isset($input['due_date'])) {
                $dueDate = $input['due_date'];
                if ($dueDate === '' || $dueDate === null) {
                    $fieldsToUpdate['due_date'] = null;
                    $changes['due_date'] = ['old' => $task['due_date'], 'new' => null];
                } else {
                    $dateObj = \DateTime::createFromFormat('Y-m-d', $dueDate);
                    $today = new \DateTime('today');
                    if ($dateObj && $dateObj >= $today) {
                        $fieldsToUpdate['due_date'] = $dueDate;
                        $changes['due_date'] = ['old' => $task['due_date'], 'new' => $dueDate];
                    }
                }
            }

            if (array_key_exists('assigned_to', $input)) {
                $assignedTo = $input['assigned_to'] !== null && $input['assigned_to'] !== '' ? (int)$input['assigned_to'] : null;
                if ($assignedTo != $task['assigned_to']) {
                    $fieldsToUpdate['assigned_to'] = $assignedTo;
                    $changes['assigned_to'] = ['old' => $task['assigned_to'], 'new' => $assignedTo];
                }
            }

            if (empty($fieldsToUpdate)) {
                $this->failure("No changes detected.", [], HTTP_BAD_REQUEST);
            }

            $updated = $taskModel->update($taskId, $fieldsToUpdate);

            if ($updated) {
                // Log activity
                $userId = (int)Session::get('userId');
                $activityLog = new TaskActivityLog();
                $activityLog->logActivity($task['project_id'], $taskId, $userId, 'updated', $changes);

                // Send email if assignee changed to a new person
                if (isset($changes['assigned_to']) && $changes['assigned_to']['new'] !== null) {
                    try {
                        $userModel = new Users();
                        $assignee = $userModel->getUserDetailsById($changes['assigned_to']['new']);
                        $assigner = $userModel->getUserDetailsById($userId);
                        $projectModel = new Projects();
                        $project = $projectModel->getById($task['project_id'], true);

                        if ($assignee && $assigner && $project) {
                            $priorities = ['1' => 'low', '2' => 'medium', '3' => 'high'];
                            $priorityId = $fieldsToUpdate['task_priority_id'] ?? $task['task_priority_id'];
                            $emailService = new EmailService();
                            $emailService->sendTemplateMail(
                                $assignee[0]['email'],
                                $assignee[0]['first_name'] . ' ' . $assignee[0]['last_name'],
                                'Task Assigned: ' . ($fieldsToUpdate['name'] ?? $task['name']),
                                'task_assigned',
                                [
                                    'assigneeName' => $assignee[0]['first_name'] . ' ' . $assignee[0]['last_name'],
                                    'assignerName' => $assigner[0]['first_name'] . ' ' . $assigner[0]['last_name'],
                                    'taskName' => $fieldsToUpdate['name'] ?? $task['name'],
                                    'projectName' => $project['name'],
                                    'priority' => $priorities[$priorityId] ?? 'medium',
                                    'dueDate' => isset($fieldsToUpdate['due_date']) ? ($fieldsToUpdate['due_date'] ? date('M d, Y', strtotime($fieldsToUpdate['due_date'])) : '') : ($task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : ''),
                                    'description' => $fieldsToUpdate['description'] ?? $task['description']
                                ]
                            );
                        }
                    } catch (Throwable $emailError) {
                        Logger::error($emailError);
                    }
                }

                $this->success("Task updated.");
            }

            $this->failure("Failed to update.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete task (API)
     */
    public function deleteTask(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = (int)($input['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }

            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            $deleted = $taskModel->softDelete($taskId);

            if ($deleted) {
                // Log activity
                $userId = (int)Session::get('userId');
                $activityLog = new TaskActivityLog();
                $activityLog->logActivity($task['project_id'], $taskId, $userId, 'deleted', ['task_name' => $task['name']]);

                $this->success("Task deleted.");
            }

            $this->failure("Failed to delete.", [], HTTP_INTERNAL_SERVER_ERROR);
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
            $taskId = (int)($_GET['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
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
            $userId = (int)Session::get('userId');
            $taskId = (int)($_POST['task_id'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
            }
            if (empty($comment)) {
                $this->failure("Comment cannot be empty.", [], HTTP_BAD_REQUEST);
            }

            // Get task to retrieve project_id
            $taskModel = new ProjectTasks();
            $task = $taskModel->getById($taskId);

            if (!$task) {
                $this->failure("Task not found.", [], HTTP_NOT_FOUND);
            }

            $commentModel = new TaskComments();
            $commentId = $commentModel->create($task['project_id'], $userId, $comment, $taskId);

            if ($commentId) {
                // Log activity
                $activityLog = new TaskActivityLog();
                $activityLog->logActivity($task['project_id'], $taskId, $userId, 'comment_added', ['comment_id' => $commentId]);

                $comments = $commentModel->getByTask($taskId);
                $newComment = array_pop($comments); // Get the latest added

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
            $taskId = (int)($_GET['task_id'] ?? 0);

            if ($taskId <= 0) {
                $this->failure("Invalid task ID.", [], HTTP_BAD_REQUEST);
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
     * Get project activity logs (API)
     */
    public function getProjectActivityLogs(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $projectId = (int)($_GET['project_id'] ?? 0);

            if ($projectId <= 0) {
                $this->failure("Invalid project ID.", [], HTTP_BAD_REQUEST);
            }

            $activityLog = new TaskActivityLog();
            $logs = $activityLog->getProjectLogs($projectId, 100, 0);

            $this->success("Project activity logs fetched.", $logs);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get assignable employees for a project
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

            $projectModel = new Projects();
            $assignees = $projectModel->getProjectAssignees($projectId);

            $this->success("Assignees fetched.", ['assignees' => $assignees]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
