<?php

namespace App\Models;

use App\Core\Model;

class ProjectTasks extends Model
{

    protected string $tableName = "project_tasks";

    /**
     * Create a new task
     */
    public function create(int $projectId, int $createdBy, string $name, string $description, int $priorityId, int $statusId, ?string $dueDate, ?int $assignedTo = null): int|false
    {
        $sql = "INSERT INTO {$this->tableName} (project_id, created_by, assigned_to, name, description, task_priority_id, task_status_id, due_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $success = $this->rawExecute($sql, "iiissiis", [$projectId, $createdBy, $assignedTo, $name, $description, $priorityId, $statusId, $dueDate]);

        if ($success) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Get task by ID with details
     */
    public function getById(int $taskId): ?array
    {
        $sql = "SELECT 
            t.*,
            ts.name as status_name,
            tp.name as priority_name,
            p.name as project_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            assigned.first_name as assigned_first_name,
            assigned.last_name as assigned_last_name,
            assigned.email as assigned_email
        FROM {$this->tableName} t
        JOIN task_statuses ts ON t.task_status_id = ts.id
        JOIN task_priorities tp ON t.task_priority_id = tp.id
        JOIN projects p ON t.project_id = p.id
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users assigned ON t.assigned_to = assigned.id
        WHERE t.id = ? AND t.is_deleted = 0
        LIMIT 1";

        $result = $this->rawQuery($sql, "i", [$taskId]);
        return $result[0] ?? null;
    }

    /**
     * Get all tasks (for admin)
     */
    public function getAllPaginated(string $search = '', ?int $statusFilter = null, ?int $priorityFilter = null, ?int $projectFilter = null, string $sortColumn = 't.created_at DESC', int $limit = 10, int $offset = 0): array
    {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE t.is_deleted = 0";

        if ($statusFilter !== null) {
            $WHERE .= " AND t.task_status_id = " . (int)$statusFilter;
        }
        if ($priorityFilter !== null) {
            $WHERE .= " AND t.task_priority_id = " . (int)$priorityFilter;
        }
        if ($projectFilter !== null) {
            $WHERE .= " AND t.project_id = " . (int)$projectFilter;
        }

        if ($search !== '') {
            $WHERE .= " AND (
                t.name LIKE '%$search%' OR
                t.description LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            t.*,
            ts.name as status_name,
            tp.name as priority_name,
            p.name as project_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            assigned.first_name as assigned_first_name,
            assigned.last_name as assigned_last_name,
            assigned.email as assigned_email
        FROM {$this->tableName} t
        JOIN task_statuses ts ON t.task_status_id = ts.id
        JOIN task_priorities tp ON t.task_priority_id = tp.id
        JOIN projects p ON t.project_id = p.id
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users assigned ON t.assigned_to = assigned.id
        {$WHERE}
        ORDER BY {$sortColumn}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "ii", [$limit, $offset]);

        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} t {$WHERE}";
        $countResult = $this->rawQuery($countSql);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get tasks by project
     */
    public function getByProject(int $projectId, string $search = '', ?int $statusFilter = null, ?int $priorityFilter = null, int $limit = 10, int $offset = 0): array
    {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE t.is_deleted = 0 AND t.project_id = ?";

        if ($statusFilter !== null) {
            $WHERE .= " AND t.task_status_id = " . (int)$statusFilter;
        }
        if ($priorityFilter !== null) {
            $WHERE .= " AND t.task_priority_id = " . (int)$priorityFilter;
        }

        if ($search !== '') {
            $WHERE .= " AND (
                t.name LIKE '%$search%' OR
                t.description LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            t.*,
            ts.name as status_name,
            tp.name as priority_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            assigned.first_name as assigned_first_name,
            assigned.last_name as assigned_last_name,
            assigned.email as assigned_email
        FROM {$this->tableName} t
        JOIN task_statuses ts ON t.task_status_id = ts.id
        JOIN task_priorities tp ON t.task_priority_id = tp.id
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users assigned ON t.assigned_to = assigned.id
        {$WHERE}
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$projectId, $limit, $offset]);

        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} t WHERE t.is_deleted = 0 AND t.project_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$projectId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get tasks created by a specific user (for manager - tasks in their projects)
     */
    public function getByCreator(int $userId, string $search = '', ?int $statusFilter = null, ?int $priorityFilter = null, string $sortColumn = 't.created_at DESC', int $limit = 10, int $offset = 0): array
    {
        $search = $this->getSanitizedInput($search);

        // Get tasks from projects created by this user
        $WHERE = "WHERE t.is_deleted = 0 AND p.created_by = ? AND p.is_deleted = 0";

        if ($statusFilter !== null) {
            $WHERE .= " AND t.task_status_id = " . (int)$statusFilter;
        }
        if ($priorityFilter !== null) {
            $WHERE .= " AND t.task_priority_id = " . (int)$priorityFilter;
        }

        if ($search !== '') {
            $WHERE .= " AND (
                t.name LIKE '%$search%' OR
                t.description LIKE '%$search%' OR
                p.name LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            t.*,
            ts.name as status_name,
            tp.name as priority_name,
            p.name as project_name,
            assigned.first_name as assigned_first_name,
            assigned.last_name as assigned_last_name,
            assigned.email as assigned_email
        FROM {$this->tableName} t
        JOIN task_statuses ts ON t.task_status_id = ts.id
        JOIN task_priorities tp ON t.task_priority_id = tp.id
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN users assigned ON t.assigned_to = assigned.id
        {$WHERE}
        ORDER BY {$sortColumn}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$userId, $limit, $offset]);

        $countSql = "SELECT COUNT(*) as total 
            FROM {$this->tableName} t 
            JOIN projects p ON t.project_id = p.id
            WHERE t.is_deleted = 0 AND p.created_by = ? AND p.is_deleted = 0";
        $countResult = $this->rawQuery($countSql, "i", [$userId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get tasks assigned to a user (for employee)
     */
    public function getByAssignedUser(int $userId, string $search = '', ?int $statusFilter = null, ?int $priorityFilter = null, int $limit = 10, int $offset = 0): array
    {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE t.is_deleted = 0 AND pua.user_id = ?";

        if ($statusFilter !== null) {
            $WHERE .= " AND t.task_status_id = " . (int)$statusFilter;
        }
        if ($priorityFilter !== null) {
            $WHERE .= " AND t.task_priority_id = " . (int)$priorityFilter;
        }

        if ($search !== '') {
            $WHERE .= " AND (
                t.name LIKE '%$search%' OR
                t.description LIKE '%$search%' OR
                p.name LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            t.*,
            ts.name as status_name,
            tp.name as priority_name,
            p.name as project_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            assigned.first_name as assigned_first_name,
            assigned.last_name as assigned_last_name,
            assigned.email as assigned_email
        FROM {$this->tableName} t
        JOIN task_statuses ts ON t.task_status_id = ts.id
        JOIN task_priorities tp ON t.task_priority_id = tp.id
        JOIN projects p ON t.project_id = p.id
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users assigned ON t.assigned_to = assigned.id
        JOIN project_user_assignments pua ON p.id = pua.project_id
        {$WHERE}
        ORDER BY t.due_date ASC, t.task_priority_id DESC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$userId, $limit, $offset]);

        $countSql = "SELECT COUNT(*) as total 
            FROM {$this->tableName} t 
            JOIN projects p ON t.project_id = p.id
            JOIN project_user_assignments pua ON p.id = pua.project_id
            WHERE t.is_deleted = 0 AND pua.user_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$userId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Update task status (for AJAX)
     */
    public function updateStatus(int $taskId, int $statusId): bool
    {
        $sql = "UPDATE {$this->tableName} SET task_status_id = ? WHERE id = ? AND is_deleted = 0";
        return $this->rawExecute($sql, "ii", [$statusId, $taskId]);
    }

    /**
     * Soft delete a task
     */
    public function softDelete(int $taskId): bool
    {
        $sql = "UPDATE {$this->tableName} SET is_deleted = 1 WHERE id = ?";
        return $this->rawExecute($sql, "i", [$taskId]);
    }

    /**
     * Update task details
     */
    public function update(int $taskId, array $updates): bool
    {
        $setParts = [];
        $params = [];
        $types = "";

        if (isset($updates['name'])) {
            $setParts[] = "name = ?";
            $params[] = $updates['name'];
            $types .= "s";
        }
        if (isset($updates['description'])) {
            $setParts[] = "description = ?";
            $params[] = $updates['description'];
            $types .= "s";
        }
        if (isset($updates['task_priority_id'])) {
            $setParts[] = "task_priority_id = ?";
            $params[] = $updates['task_priority_id'];
            $types .= "i";
        }
        if (isset($updates['task_status_id'])) {
            $setParts[] = "task_status_id = ?";
            $params[] = $updates['task_status_id'];
            $types .= "i";
        }
        if (isset($updates['due_date'])) {
            $setParts[] = "due_date = ?";
            $params[] = $updates['due_date'];
            $types .= "s";
        }
        if (array_key_exists('assigned_to', $updates)) {
            $setParts[] = "assigned_to = ?";
            $params[] = $updates['assigned_to'];
            $types .= "i";
        }

        if (empty($setParts)) {
            return false;
        }

        $params[] = $taskId;
        $types .= "i";

        $sql = "UPDATE {$this->tableName} SET " . implode(", ", $setParts) . " WHERE id = ? AND is_deleted = 0";
        return $this->rawExecute($sql, $types, $params);
    }

    /**
     * Get comments for a task
     */
    public function getComments(int $taskId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
            c.id,
            c.task_id,
            c.author_id,
            c.comment,
            c.created_at,
            u.first_name,
            u.last_name,
            u.email
        FROM project_task_comments c
        JOIN users u ON c.author_id = u.id
        WHERE c.task_id = ?
        ORDER BY c.created_at ASC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$taskId, $limit, $offset]);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM project_task_comments WHERE task_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$taskId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Add a comment to a task
     */
    public function addComment(int $taskId, int $authorId, string $comment): int|false
    {
        $sql = "INSERT INTO project_task_comments (task_id, author_id, comment) VALUES (?, ?, ?)";
        $success = $this->rawExecute($sql, "iis", [$taskId, $authorId, $comment]);

        if ($success) {
            return $this->db->insert_id;
        }
        return false;
    }


    /**
     * Assign user to task (via project assignment)
     */
    public function assignUser(int $taskId, int $userId): bool
    {
        // Get project_id for this task
        $task = $this->getById($taskId);
        if (!$task) return false;

        // Assign user to project (if not already)
        $sql = "INSERT INTO project_user_assignments (project_id, user_id) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE project_id = project_id";
        return $this->rawExecute($sql, "ii", [$task['project_id'], $userId]);
    }

    /**
     * Get task statuses
     */
    public function getStatuses(): array
    {
        $sql = "SELECT id, name FROM task_statuses ORDER BY id ASC";
        return $this->rawQuery($sql);
    }

    /**
     * Get task priorities
     */
    public function getPriorities(): array
    {
        $sql = "SELECT id, name FROM task_priorities ORDER BY id ASC";
        return $this->rawQuery($sql);
    }

    /**
     * Get task counts by status for dashboard
     */
    public function getStatusCounts(?int $userId = null, ?int $projectId = null): array
    {
        $WHERE = "WHERE t.is_deleted = 0";
        $params = [];
        $types = "";

        if ($userId !== null) {
            $WHERE .= " AND p.created_by = ?";
            $params[] = $userId;
            $types .= "i";
        }
        if ($projectId !== null) {
            $WHERE .= " AND t.project_id = ?";
            $params[] = $projectId;
            $types .= "i";
        }

        $sql = "SELECT 
            ts.name as status,
            COUNT(t.id) as count
        FROM task_statuses ts
        LEFT JOIN {$this->tableName} t ON ts.id = t.task_status_id AND t.is_deleted = 0
        LEFT JOIN projects p ON t.project_id = p.id AND p.is_deleted = 0
        " . ($WHERE !== "WHERE t.is_deleted = 0" ? $WHERE : "") . "
        GROUP BY ts.id, ts.name
        ORDER BY ts.id ASC";

        if (empty($types)) {
            return $this->rawQuery($sql);
        }
        return $this->rawQuery($sql, $types, $params);
    }

    /**
     * Get tasks due soon (within 3 days) - only tasks assigned to the user
     */
    public function getDueSoon(int $userId, int $limit = 5): array
    {
        $sql = "SELECT 
            t.*,
            ts.name as status_name,
            tp.name as priority_name,
            p.name as project_name
        FROM {$this->tableName} t
        JOIN task_statuses ts ON t.task_status_id = ts.id
        JOIN task_priorities tp ON t.task_priority_id = tp.id
        JOIN projects p ON t.project_id = p.id
        WHERE t.is_deleted = 0 
        AND p.is_deleted = 0
        AND t.assigned_to = ?
        AND t.due_date IS NOT NULL 
        AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        AND t.task_status_id != 3
        ORDER BY t.due_date ASC
        LIMIT ?";

        return $this->rawQuery($sql, "ii", [$userId, $limit]);
    }

    /**
     * Get tasks for Kanban board (grouped by status)
     * @param int $projectId Project to get tasks for
     * @param int|null $userId If provided, only show tasks assigned to this user (employee view)
     */
    public function getForKanban(int $projectId, ?int $userId = null): array
    {
        $WHERE = "WHERE t.is_deleted = 0 AND t.project_id = ?";
        $params = [$projectId];
        $types = "i";

        // For employees, only show their assigned tasks
        if ($userId !== null) {
            $WHERE .= " AND t.assigned_to = ?";
            $params[] = $userId;
            $types .= "i";
        }

        $sql = "SELECT 
            t.id,
            t.project_id,
            t.name,
            t.description,
            t.task_status_id,
            t.task_priority_id,
            t.due_date,
            t.assigned_to,
            t.created_at,
            ts.name as status_name,
            tp.name as priority_name,
            assigned.first_name as assigned_first_name,
            assigned.last_name as assigned_last_name,
            (SELECT COUNT(*) FROM project_task_comments WHERE task_id = t.id) as comment_count
        FROM {$this->tableName} t
        JOIN task_statuses ts ON t.task_status_id = ts.id
        JOIN task_priorities tp ON t.task_priority_id = tp.id
        LEFT JOIN users assigned ON t.assigned_to = assigned.id
        {$WHERE}
        ORDER BY t.task_priority_id DESC, t.due_date ASC, t.created_at DESC";

        $tasks = $this->rawQuery($sql, $types, $params);

        // Group by status
        $grouped = [
            'todo' => [],
            'in_progress' => [],
            'done' => [],
            'blocked' => []
        ];

        foreach ($tasks as $task) {
            $status = $task['status_name'];
            if (isset($grouped[$status])) {
                $grouped[$status][] = $task;
            }
        }

        return $grouped;
    }

    /**
     * Check if user can access task based on role
     * Admin: any task
     * Manager: tasks in their projects
     * Employee: tasks assigned to them OR tasks in projects they're assigned to
     */
    public function canUserAccessTask(int $taskId, int $userId, int $roleId): bool
    {
        // Admin can access any task
        if ($roleId === 1) {
            return true;
        }

        $task = $this->getById($taskId);
        if (!$task) {
            return false;
        }

        // Manager: check if they manage the project
        if ($roleId === 2) {
            $sql = "SELECT id FROM projects WHERE id = ? AND manager_id = ? AND is_deleted = 0";
            $result = $this->rawQuery($sql, "ii", [$task['project_id'], $userId]);
            return count($result) > 0;
        }

        // Employee: check if assigned to project
        if ($roleId === 3) {
            $sql = "SELECT id FROM project_user_assignments WHERE project_id = ? AND user_id = ?";
            $result = $this->rawQuery($sql, "ii", [$task['project_id'], $userId]);
            return count($result) > 0;
        }

        return false;
    }

    /**
     * Check if user can modify task (create/edit/delete)
     * Only Admin and Manager (of the project) can modify
     */
    public function canUserModifyTask(int $projectId, int $userId, int $roleId): bool
    {
        // Admin can modify any task
        if ($roleId === 1) {
            return true;
        }

        // Manager: check if they manage the project
        if ($roleId === 2) {
            $sql = "SELECT id FROM projects WHERE id = ? AND manager_id = ? AND is_deleted = 0";
            $result = $this->rawQuery($sql, "ii", [$projectId, $userId]);
            return count($result) > 0;
        }

        return false;
    }

    /**
     * Check if drag-drop is allowed (project must be in_progress)
     */
    public function isDragDropAllowed(int $projectId): bool
    {
        $sql = "SELECT project_status_id FROM projects WHERE id = ? AND is_deleted = 0";
        $result = $this->rawQuery($sql, "i", [$projectId]);
        // Status 2 = in_progress
        return !empty($result) && (int)$result[0]['project_status_id'] === 2;
    }

    /**
     * Get dashboard statistics for tasks (Admin/Manager)
     * @param int|null $managerId Filter by manager's projects
     */
    public function getDashboardStats(?int $managerId = null): array
    {
        $condition = $managerId !== null ? "p.manager_id = ?" : "1=1";
        $params = $managerId !== null ? [$managerId] : [];
        $types = $managerId !== null ? "i" : "";

        $sql = "SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN t.task_status_id = 3 THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE() AND t.task_status_id != 3 THEN 1 ELSE 0 END) as overdue_tasks
        FROM {$this->tableName} t
        JOIN projects p ON t.project_id = p.id
        WHERE t.is_deleted = 0 AND p.is_deleted = 0 AND {$condition}";

        $result = $this->rawQuery($sql, $types, $params);
        return $result[0] ?? [
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'overdue_tasks' => 0
        ];
    }

    /**
     * Get dashboard statistics for employee's assigned tasks
     */
    public function getEmployeeDashboardStats(int $userId): array
    {
        $sql = "SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN t.task_status_id = 3 THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.task_status_id = 2 THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE() AND t.task_status_id != 3 THEN 1 ELSE 0 END) as overdue_tasks,
            SUM(CASE WHEN t.due_date = CURDATE() AND t.task_status_id != 3 THEN 1 ELSE 0 END) as due_today
        FROM {$this->tableName} t
        JOIN projects p ON t.project_id = p.id
        WHERE t.is_deleted = 0 AND p.is_deleted = 0 AND t.assigned_to = ?";

        $result = $this->rawQuery($sql, "i", [$userId]);
        return $result[0] ?? [
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'in_progress_tasks' => 0,
            'overdue_tasks' => 0,
            'due_today' => 0
        ];
    }
}
