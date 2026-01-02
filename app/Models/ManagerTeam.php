<?php

namespace App\Models;

use App\Core\Model;

class ManagerTeam extends Model
{

    protected string $tableName = "manager_team_members";

    /**
     * Get all team members for a manager
     */
    public function getTeamMembers(
        int $managerId,
        string $search = '',
        ?int $activeStatus = 1,
        string $sortBy = 'assigned_at',
        string $sortOrder = 'DESC',
        int $limit = 10,
        int $offset = 0
    ): array {
        $search = $this->getSanitizedInput($search);

        $WHERE = "WHERE mtm.manager_id = ? AND mtm.is_active = 1";
        $params = [$managerId];
        $types = "i";

        // Status filter (user's active status, not assignment)
        if ($activeStatus !== null) {
            $WHERE .= " AND u.is_active = ?";
            $params[] = $activeStatus;
            $types .= "i";
        }

        if ($search !== '') {
            $WHERE .= " AND (
                u.first_name LIKE '%$search%' OR
                u.last_name LIKE '%$search%' OR
                u.email LIKE '%$search%' OR
                u.phone_number LIKE '%$search%'
            )";
        }

        // Validate sort_by
        $validSortBy = ['first_name', 'last_name', 'email', 'assigned_at', 'project_count', 'task_count'];
        if (!in_array($sortBy, $validSortBy)) {
            $sortBy = 'assigned_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        // Map sort field
        $sortField = match ($sortBy) {
            'first_name' => 'u.first_name',
            'last_name' => 'u.last_name',
            'email' => 'u.email',
            'project_count' => 'project_count',
            'task_count' => 'task_count',
            default => 'mtm.created_at'
        };

        $sql = "SELECT 
            mtm.id as assignment_id,
            u.id as user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            u.is_active,
            mtm.created_at as assigned_at,
            (SELECT COUNT(DISTINCT pua.project_id) FROM project_user_assignments pua WHERE pua.user_id = u.id) as project_count,
            (SELECT COUNT(*) FROM project_tasks pt WHERE pt.assigned_to = u.id AND pt.is_deleted = 0) as task_count
        FROM {$this->tableName} mtm
        JOIN users u ON mtm.member_id = u.id
        {$WHERE}
        ORDER BY {$sortField} {$sortOrder}
        LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $data = $this->rawQuery($sql, $types, $params);

        // Get total count
        $countParams = [$managerId];
        $countTypes = "i";
        if ($activeStatus !== null) {
            $countParams[] = $activeStatus;
            $countTypes .= "i";
        }
        $countSql = "SELECT COUNT(*) as total 
            FROM {$this->tableName} mtm 
            JOIN users u ON mtm.member_id = u.id
            {$WHERE}";
        $countResult = $this->rawQuery($countSql, $countTypes, $countParams);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Add a member to manager's team
     */
    public function addMember(int $managerId, int $memberId): bool
    {
        $sql = "INSERT INTO {$this->tableName} (manager_id, member_id, is_active) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE is_active = 1";
        return $this->rawExecute($sql, "ii", [$managerId, $memberId]);
    }

    /**
     * Remove a member from manager's team (soft delete)
     */
    public function removeMember(int $managerId, int $memberId): bool
    {
        $sql = "UPDATE {$this->tableName} SET is_active = 0 WHERE manager_id = ? AND member_id = ?";
        return $this->rawExecute($sql, "ii", [$managerId, $memberId]);
    }

    /**
     * Check if an employee is assigned to any manager
     */
    public function isEmployeeAssigned(int $employeeId): bool
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE member_id = ? AND is_active = 1 LIMIT 1";
        $result = $this->rawQuery($sql, "i", [$employeeId]);
        return count($result) > 0;
    }

    /**
     * Get the manager of an employee
     */
    public function getEmployeeManager(int $employeeId): ?array
    {
        $sql = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email
        FROM {$this->tableName} mtm
        JOIN users u ON mtm.manager_id = u.id
        WHERE mtm.member_id = ? AND mtm.is_active = 1
        LIMIT 1";

        $result = $this->rawQuery($sql, "i", [$employeeId]);
        return $result[0] ?? null;
    }

    /**
     * Get all employees (with current manager info if assigned)
     * Returns all active employees - for reassignment support
     */
    public function getUnassignedEmployees(): array
    {
        $sql = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            CASE WHEN mtm.id IS NOT NULL THEN 1 ELSE 0 END as is_assigned,
            m.first_name as current_manager_first_name,
            m.last_name as current_manager_last_name
        FROM users u
        LEFT JOIN {$this->tableName} mtm ON u.id = mtm.member_id AND mtm.is_active = 1
        LEFT JOIN users m ON mtm.manager_id = m.id
        WHERE u.role_id = 3 
        AND u.is_active = 1
        ORDER BY u.first_name ASC";

        return $this->rawQuery($sql);
    }

    /**
     * Get all team assignments (for admin view)
     */
    public function getAllTeamAssignments(string $search = '', int $limit = 10, int $offset = 0, int $managerId = 0, string $sortBy = 'assigned_at', string $sortOrder = 'DESC'): array
    {
        $search = $this->getSanitizedInput($search);
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $WHERE = "WHERE mtm.is_active = 1";

        if ($search !== '') {
            $WHERE .= " AND (
                m.first_name LIKE '%$search%' OR
                m.last_name LIKE '%$search%' OR
                e.first_name LIKE '%$search%' OR
                e.last_name LIKE '%$search%'
            )";
        }

        if ($managerId > 0) {
            $WHERE .= " AND mtm.manager_id = $managerId";
        }

        // Determine ORDER BY
        $orderBy = match ($sortBy) {
            'manager_name' => "m.first_name {$sortOrder}, m.last_name {$sortOrder}",
            'employee_name' => "e.first_name {$sortOrder}, e.last_name {$sortOrder}",
            'assigned_at' => "mtm.created_at {$sortOrder}",
            default => "mtm.created_at {$sortOrder}"
        };

        $sql = "SELECT 
            mtm.id as assignment_id,
            m.id as manager_id,
            m.first_name as manager_first_name,
            m.last_name as manager_last_name,
            m.email as manager_email,
            e.id as employee_id,
            e.first_name as employee_first_name,
            e.last_name as employee_last_name,
            e.email as employee_email,
            mtm.created_at as assigned_at
        FROM {$this->tableName} mtm
        JOIN users m ON mtm.manager_id = m.id
        JOIN users e ON mtm.member_id = e.id
        {$WHERE}
        ORDER BY {$orderBy}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "ii", [$limit, $offset]);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
            FROM {$this->tableName} mtm 
            JOIN users m ON mtm.manager_id = m.id
            JOIN users e ON mtm.member_id = e.id
            {$WHERE}";
        $countResult = $this->rawQuery($countSql);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Admin: Force assign employee to manager (even if already assigned elsewhere)
     */
    public function adminAssignEmployee(int $managerId, int $memberId): bool
    {
        // First, remove from any existing team
        $removeSql = "UPDATE {$this->tableName} SET is_active = 0 WHERE member_id = ? AND is_active = 1";
        $this->rawExecute($removeSql, "i", [$memberId]);

        // Then add to new team
        return $this->addMember($managerId, $memberId);
    }

    /**
     * Check if employee is in manager's team
     */
    public function isInManagerTeam(int $managerId, int $employeeId): bool
    {
        $sql = "SELECT id FROM {$this->tableName} 
                WHERE manager_id = ? AND member_id = ? AND is_active = 1 LIMIT 1";
        $result = $this->rawQuery($sql, "ii", [$managerId, $employeeId]);
        return count($result) > 0;
    }

    /**
     * Get all managers with their statistics (project count and team member count)
     */
    public function getAllManagersWithStats(string $search = '', int $limit = 10, int $offset = 0, string $sortBy = 'manager_name', string $sortOrder = 'ASC'): array
    {
        $search = $this->getSanitizedInput($search);
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $WHERE = "WHERE u.role_id = 2 AND u.is_active = 1";

        if ($search !== '') {
            $WHERE .= " AND (
                u.first_name LIKE '%$search%' OR
                u.last_name LIKE '%$search%' OR
                u.email LIKE '%$search%'
            )";
        }

        // Determine ORDER BY
        $orderBy = match ($sortBy) {
            'manager_name' => "u.first_name {$sortOrder}, u.last_name {$sortOrder}",
            'team_count' => "team_count {$sortOrder}",
            'project_count' => "project_count {$sortOrder}",
            default => "u.first_name {$sortOrder}"
        };

        $sql = "SELECT 
            u.id as manager_id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            COUNT(DISTINCT CASE WHEN mtm.is_active = 1 THEN mtm.member_id END) as team_count,
            COUNT(DISTINCT p.id) as project_count
        FROM users u
        LEFT JOIN {$this->tableName} mtm ON u.id = mtm.manager_id
        LEFT JOIN projects p ON u.id = p.created_by AND p.is_deleted = 0
        {$WHERE}
        GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone_number
        ORDER BY {$orderBy}
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "ii", [$limit, $offset]);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
            FROM users u
            {$WHERE}";
        $countResult = $this->rawQuery($countSql);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get unassigned employees for a specific manager (employees not in that manager's team)
     */
    public function getUnassignedEmployeesForManager(int $managerId): array
    {
        $sql = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number
        FROM users u
        WHERE u.role_id = 3 
        AND u.is_active = 1
        AND u.id NOT IN (
            SELECT member_id FROM {$this->tableName} 
            WHERE manager_id = ? AND is_active = 1
        )
        ORDER BY u.first_name ASC";

        return $this->rawQuery($sql, "i", [$managerId]);
    }

    /**
     * Get team member count for a manager
     */
    public function getTeamMemberCount(int $managerId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->tableName} WHERE manager_id = ? AND is_active = 1";
        $result = $this->rawQuery($sql, "i", [$managerId]);
        return (int)($result[0]['count'] ?? 0);
    }
}
