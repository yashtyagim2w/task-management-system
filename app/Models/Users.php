<?php
namespace App\Models;

use App\Core\Model;

class Users extends Model {

    protected string $tableName = "users";
    
    public function getByEmail(string $email): array {
        $stmt = "SELECT 
            u.id,
            u.first_name,   
            u.last_name,
            u.email,
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

    public function getUserDetailsById(int $id): array {
        $stmt = "SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.password,
            u.role_id,
            u.is_active,
            r.name AS role_name
        FROM {$this->tableName} u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ? 
        LIMIT 1;";

        $types = "i";
        $result = $this->rawQuery($stmt, $types, [$id]);
        return $result;
    }

    public function existByEmailOrPhone(string $email, string $phone): bool {
        $sql = "SELECT id FROM {$this->tableName} WHERE email = ? OR phone_number = ? LIMIT 1;";
        $result = $this->rawQuery($sql, "ss", [$email, $phone]);

        return count($result) > 0;
    }

    public function create(string $firstName, string $lastName, string $email, string $phone, string $hashedPassword, int $roleId, int $createdBy): bool {
        $sql = "INSERT INTO {$this->tableName} (first_name, last_name, email, phone_number, password, role_id, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
        $types = "ssssssii";
        $params = [$firstName, $lastName, $email, $phone, $hashedPassword, $roleId, 1, $createdBy];
        return $this->rawExecute($sql, $types, $params);
    }

    public function getAllUserPaginated(string $search, string $sortColumn, ?int $roleFilter, ?int $activeStatusFilter, int $currentUserId, int $limit, int $offset): array {
        
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

        $sql = "SELECT
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            r.name AS role_name,
            u.is_active,
            u.created_at
        FROM {$this->tableName} u
        JOIN roles r ON u.role_id = r.id
        {$WHERE}
        {$ORDER}
        LIMIT ? OFFSET ?
        ;";

        $types = "ii";
        $params = [$limit, $offset];
    
        return $this->rawQuery($sql, $types, $params);

    }
}