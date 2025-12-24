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

}