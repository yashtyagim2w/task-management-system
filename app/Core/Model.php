<?php
namespace App\Core;

use mysqli;

abstract class Model {
    
    protected mysqli $db;
    protected string $tableName;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // to find record by id
    public function findById(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM { $this->tableName } WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        return $record;
    }

    // select raw query execution
    public function rawQuery(string $sql, string $types = "", array $params = []): array {
        $stmt = $this->db->prepare($sql);
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return $result;
    }

    // raw query execution for insert, update, delete
    public function rawExecute(string $sql, string $types = "", array $params = []): bool {
        $stmt = $this->db->prepare($sql);
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        return $stmt->execute();
    }

}