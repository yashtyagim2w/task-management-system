<?php
namespace App\Models;

use App\Core\Model;

class Roles extends Model {

    protected string $tableName = "roles";

    public function getAllRolesExceptSuperAdmin(): array {
        $sql = "SELECT id, name, description, created_at, updated_at FROM {$this->tableName} WHERE id != 1;";
        return $this->rawQuery($sql);
    }
    
}