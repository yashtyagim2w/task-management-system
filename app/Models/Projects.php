<?php

namespace App\Models;

use App\Core\Model;

class Projects extends Model
{

    protected string $tableName = "projects";

    /**
     * Create a new project
     * @param int $createdBy - User who created the project
     * @param int $managerId - Manager assigned to the project
     * @param string $name - Project name
     * @param string $description - Project description
     * @param int $statusId - Project status ID (default: 1 = pending)
     */
    public function create(int $createdBy, int $managerId, string $name, string $description, int $statusId = 1): int|false
    {
        $sql = "INSERT INTO {$this->tableName} (created_by, manager_id, name, description, project_status_id) 
                VALUES (?, ?, ?, ?, ?)";
        $success = $this->rawExecute($sql, "iissi", [$createdBy, $managerId, $name, $description, $statusId]);

        if ($success) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Get project by ID with manager info
     */
    public function getById(int $projectId, bool $includeDeleted = false): ?array
    {
        $deletedCondition = $includeDeleted ? "" : "AND p.is_deleted = 0";

        $sql = "SELECT 
            p.*,
            ps.name as status_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            u.email as creator_email,
            m.first_name as manager_first_name,
            m.last_name as manager_last_name,
            m.email as manager_email,
            (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND is_deleted = 0) as task_count
        FROM {$this->tableName} p
        JOIN project_statuses ps ON p.project_status_id = ps.id
        JOIN users u ON p.created_by = u.id
        JOIN users m ON p.manager_id = m.id
        WHERE p.id = ? {$deletedCondition}
        LIMIT 1";

        $result = $this->rawQuery($sql, "i", [$projectId]);
        return $result[0] ?? null;
    }

    /**
     * Get all projects with full filtering (for admin)
     * @param string $search - Search term
     * @param int|null $managerFilter - Filter by manager ID
     * @param int|null $statusFilter - Filter by status ID
     * @param bool|null $activeFilter - Filter by is_deleted (null = all, true = active, false = deleted)
     * @param string $sortColumn - Sort column with direction
     * @param int $limit - Pagination limit
     * @param int $offset - Pagination offset
     */
    public function getAllPaginated(
        string $search = '',
        ?int $managerFilter = null,
        ?int $statusFilter = null,
        ?bool $activeFilter = true,
        string $sortColumn = 'p.created_at DESC',
        int $limit = 10,
        int $offset = 0
    ): array {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE 1=1";

        // Active filter
        if ($activeFilter !== null) {
            $WHERE .= $activeFilter ? " AND p.is_deleted = 0" : " AND p.is_deleted = 1";
        }

        // Manager filter
        if ($managerFilter !== null) {
            $WHERE .= " AND p.manager_id = " . (int)$managerFilter;
        }

        // Status filter
        if ($statusFilter !== null) {
            $WHERE .= " AND p.project_status_id = " . (int)$statusFilter;
        }

        // Search filter
        if ($search !== '') {
            $WHERE .= " AND (
                p.name LIKE '%$search%' OR
                p.description LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            p.*,
            ps.name as status_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            m.id as manager_id,
            m.first_name as manager_first_name,
            m.last_name as manager_last_name,
            m.email as manager_email,
            (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND is_deleted = 0) as task_count
        FROM {$this->tableName} p
        JOIN project_statuses ps ON p.project_status_id = ps.id
        JOIN users u ON p.created_by = u.id
        JOIN users m ON p.manager_id = m.id
        {$WHERE}
        ORDER BY {$sortColumn}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "ii", [$limit, $offset]);

        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} p 
                     JOIN users m ON p.manager_id = m.id {$WHERE}";
        $countResult = $this->rawQuery($countSql);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get projects by manager (manager's own projects)
     */
    public function getByManager(
        int $managerId,
        string $search = '',
        ?int $statusFilter = null,
        string $sortColumn = 'p.created_at DESC',
        int $limit = 10,
        int $offset = 0
    ): array {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE p.is_deleted = 0 AND p.manager_id = ?";

        if ($statusFilter !== null) {
            $WHERE .= " AND p.project_status_id = " . (int)$statusFilter;
        }

        if ($search !== '') {
            $WHERE .= " AND (
                p.name LIKE '%$search%' OR
                p.description LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            p.*,
            ps.name as status_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND is_deleted = 0) as task_count
        FROM {$this->tableName} p
        JOIN project_statuses ps ON p.project_status_id = ps.id
        JOIN users u ON p.created_by = u.id
        {$WHERE}
        ORDER BY {$sortColumn}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$managerId, $limit, $offset]);

        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} p 
                     WHERE p.is_deleted = 0 AND p.manager_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$managerId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get projects assigned to a user (for employee)
     */
    public function getByAssignedUser(
        int $userId,
        string $search = '',
        ?int $statusFilter = null,
        string $sortColumn = 'p.created_at DESC',
        int $limit = 10,
        int $offset = 0
    ): array {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE p.is_deleted = 0 AND pua.user_id = ?";

        if ($statusFilter !== null) {
            $WHERE .= " AND p.project_status_id = " . (int)$statusFilter;
        }

        if ($search !== '') {
            $WHERE .= " AND (
                p.name LIKE '%$search%' OR
                p.description LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            p.*,
            ps.name as status_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            m.first_name as manager_first_name,
            m.last_name as manager_last_name,
            (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND is_deleted = 0) as task_count
        FROM {$this->tableName} p
        JOIN project_statuses ps ON p.project_status_id = ps.id
        JOIN users u ON p.created_by = u.id
        JOIN users m ON p.manager_id = m.id
        JOIN project_user_assignments pua ON p.id = pua.project_id
        {$WHERE}
        ORDER BY {$sortColumn}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$userId, $limit, $offset]);

        $countSql = "SELECT COUNT(*) as total 
            FROM {$this->tableName} p 
            JOIN project_user_assignments pua ON p.id = pua.project_id
            WHERE p.is_deleted = 0 AND pua.user_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$userId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Soft delete a project
     */
    public function softDelete(int $projectId): bool
    {
        $sql = "UPDATE {$this->tableName} SET is_deleted = 1 WHERE id = ?";
        return $this->rawExecute($sql, "i", [$projectId]);
    }

    /**
     * Assign user to project
     */
    public function assignUser(int $projectId, int $userId): bool
    {
        $sql = "INSERT INTO project_user_assignments (project_id, user_id) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE project_id = project_id";
        return $this->rawExecute($sql, "ii", [$projectId, $userId]);
    }

    /**
     * Remove user from project
     */
    public function removeUser(int $projectId, int $userId): bool
    {
        $sql = "DELETE FROM project_user_assignments WHERE project_id = ? AND user_id = ?";
        return $this->rawExecute($sql, "ii", [$projectId, $userId]);
    }

    /**
     * Get assigned users for a project (simple list)
     */
    public function getAssignedUsers(int $projectId): array
    {
        $sql = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            pua.created_at as assigned_at
        FROM project_user_assignments pua
        JOIN users u ON pua.user_id = u.id
        WHERE pua.project_id = ?
        ORDER BY u.first_name ASC";

        return $this->rawQuery($sql, "i", [$projectId]);
    }

    /**
     * Get assigned users for a project (paginated for modal)
     */
    public function getAssignedUsersPaginated(
        int $projectId,
        string $search = '',
        string $sortColumn = 'u.first_name ASC',
        int $limit = 10,
        int $offset = 0
    ): array {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE pua.project_id = ?";

        if ($search !== '') {
            $WHERE .= " AND (
                u.first_name LIKE '%$search%' OR
                u.last_name LIKE '%$search%' OR
                u.email LIKE '%$search%'
            )";
        }

        $sql = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            u.is_active,
            pua.created_at as assigned_at
        FROM project_user_assignments pua
        JOIN users u ON pua.user_id = u.id
        {$WHERE}
        ORDER BY {$sortColumn}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$projectId, $limit, $offset]);

        $countSql = "SELECT COUNT(*) as total 
            FROM project_user_assignments pua 
            JOIN users u ON pua.user_id = u.id 
            {$WHERE}";
        $countResult = $this->rawQuery($countSql, "i", [$projectId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Check if user is assigned to project
     */
    public function isUserAssigned(int $projectId, int $userId): bool
    {
        $sql = "SELECT id FROM project_user_assignments WHERE project_id = ? AND user_id = ? LIMIT 1";
        $result = $this->rawQuery($sql, "ii", [$projectId, $userId]);
        return count($result) > 0;
    }

    /**
     * Get all project statuses
     */
    public function getStatuses(): array
    {
        $sql = "SELECT id, name FROM project_statuses ORDER BY id ASC";
        return $this->rawQuery($sql);
    }

    /**
     * Check if user has access to project (creator, manager, or assigned)
     */
    public function userHasAccess(int $projectId, int $userId): bool
    {
        $sql = "SELECT p.id FROM {$this->tableName} p
                LEFT JOIN project_user_assignments pua ON p.id = pua.project_id AND pua.user_id = ?
                WHERE p.id = ? AND p.is_deleted = 0 
                AND (p.created_by = ? OR p.manager_id = ? OR pua.user_id IS NOT NULL)
                LIMIT 1";
        $result = $this->rawQuery($sql, "iiii", [$userId, $projectId, $userId, $userId]);
        return count($result) > 0;
    }

    /**
     * Check if user is project creator
     */
    public function isCreator(int $projectId, int $userId): bool
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE id = ? AND created_by = ? AND is_deleted = 0 LIMIT 1";
        $result = $this->rawQuery($sql, "ii", [$projectId, $userId]);
        return count($result) > 0;
    }

    /**
     * Check if user is project manager
     */
    public function isManager(int $projectId, int $userId): bool
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE id = ? AND manager_id = ? AND is_deleted = 0 LIMIT 1";
        $result = $this->rawQuery($sql, "ii", [$projectId, $userId]);
        return count($result) > 0;
    }

    /**
     * Get project's manager ID
     */
    public function getProjectManagerId(int $projectId): ?int
    {
        $sql = "SELECT manager_id FROM {$this->tableName} WHERE id = ? LIMIT 1";
        $result = $this->rawQuery($sql, "i", [$projectId]);
        return $result[0]['manager_id'] ?? null;
    }

    /**
     * Get project assignees for task assignment dropdown
     */
    public function getProjectAssignees(int $projectId): array
    {
        $sql = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email
        FROM project_user_assignments pua
        JOIN users u ON pua.user_id = u.id
        WHERE pua.project_id = ? AND u.is_active = 1
        ORDER BY u.first_name ASC";

        return $this->rawQuery($sql, "i", [$projectId]);
    }
}
