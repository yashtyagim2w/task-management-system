<?php
namespace App\Core;

use mysqli;

abstract class Model {
    
    protected mysqli $db;
    protected string $tableName;

    public function __construct() {
        $this->db = Database::getConnection();
    }

}