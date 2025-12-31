<?php

namespace App\Models;

use App\Core\Model;

class Users extends Model
{

    protected string $tableName = "users";

    public function getByEmail(string $email): array
    {
        $stmt = "SELECT 
            u.id,
            u.first_name,   
            u.last_name,
            u.email,
            u.phone_number,
            u.password,
            u.role_id,
            u.is_active,
            r.name as role_name 
        FROM {$this->tableName} u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ? 
        LIMIT 1;";

        $types = "s";
        $result = $this->rawQuery($stmt, $types, [$email]);
        return $result;
    }

    public function getUserDetailsById(int $id): array
    {
        $stmt = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            u.password,
            u.role_id,
            u.is_active,
            r.name AS role_name,
            COALESCE(mt.manager_id, '-') as manager_id
        FROM {$this->tableName} u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN manager_team_members mt ON u.id = mt.member_id AND mt.is_active = 1
        WHERE u.id = ? 
        LIMIT 1;";

        $types = "i";
        $result = $this->rawQuery($stmt, $types, [$id]);
        return $result;
    }

    public function existByEmailOrPhone(string $email, string $phone): bool
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE email = ? OR phone_number = ? LIMIT 1;";
        $result = $this->rawQuery($sql, "ss", [$email, $phone]);

        return count($result) > 0;
    }

    public function create(string $firstName, string $lastName, string $email, string $phone, string $hashedPassword, int $roleId, int $createdBy): int
    {
        $sql = "INSERT INTO {$this->tableName} (first_name, last_name, email, phone_number, password, role_id, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
        $types = "ssssssii";
        $params = [$firstName, $lastName, $email, $phone, $hashedPassword, $roleId, 1, $createdBy];
        return $this->insertAndReturnId($sql, $types, $params);
    }

    public function getAllUserPaginated(string $search, string $sortColumn, ?int $roleFilter, ?int $activeStatusFilter, int $currentUserId, int $limit, int $offset): array
    {

        // Exclude current user from the list
        $WHERE = "WHERE u.id != " . $currentUserId;

        $search = $this->getSanitizedInput($search);

        if ($roleFilter !== null) {
            $WHERE .= " AND u.role_id = " . $roleFilter;
        }

        if ($activeStatusFilter !== null) {
            $WHERE .= " AND u.is_active = " . ($activeStatusFilter ? 1 : 0);
        }

        if ($search !== '') {
            $WHERE .= " AND (
                    u.first_name LIKE '%$search%' OR
                    u.last_name LIKE '%$search%' OR
                    u.email LIKE '%$search%' OR
                    u.phone_number LIKE '%$search%'
                )";
        }

        $ORDER = "ORDER BY " . $sortColumn;
        $JOIN = "LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN manager_team_members mt ON u.id = mt.member_id AND mt.is_active = 1
        LEFT JOIN users m ON mt.manager_id = m.id
        ";
        $mainAlias = "u";
        $sql = "SELECT
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            r.id AS role_id,
            r.name AS role_name,
            u.is_active,
            u.created_at,
            COALESCE(mt.manager_id, '-') as manager_id,
            COALESCE(m.email, '-') as manager_email,
            COALESCE(m.first_name, '-') as manager_first_name,
            COALESCE(m.last_name, '-') as manager_last_name
        FROM {$this->tableName} {$mainAlias}
        {$JOIN}
        {$WHERE}
        {$ORDER}
        LIMIT ? OFFSET ?
        ;";
        error_log("here: " . print_r($sql, true));
        $types = "ii";
        $params = [$limit, $offset];

        $data = $this->rawQuery($sql, $types, $params);
        $totalCount = $this->getCountWithWhereClause($mainAlias, $WHERE, $JOIN);
        return [
            "data" => $data,
            "total_count" => $totalCount
        ];
    }

    public function getAllManagers(): array
    {
        $sql = "SELECT id, first_name, last_name, email, phone_number FROM users WHERE role_id = 2;";
        $types = "";
        $result = $this->rawQuery($sql, $types);
        return $result;
    }

    public function getUsersByRole(int $roleId): array
    {
        $sql = "SELECT id, first_name, last_name, email, phone_number FROM users WHERE role_id = ? AND is_active = 1 ORDER BY first_name ASC;";
        $result = $this->rawQuery($sql, "i", [$roleId]);
        return $result;
    }

    /**
     * Get dashboard statistics for users
     */
    public function getDashboardStats(): array
    {
        $sql = "SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN role_id = 2 THEN 1 ELSE 0 END) as total_managers,
            SUM(CASE WHEN role_id = 3 THEN 1 ELSE 0 END) as total_employees
        FROM {$this->tableName}";

        $result = $this->rawQuery($sql);
        return $result[0] ?? [
            'total_users' => 0,
            'active_users' => 0,
            'total_managers' => 0,
            'total_employees' => 0
        ];
    }
}
