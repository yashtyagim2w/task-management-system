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
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE id = ?");
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

    // insert and return id
    public function insertAndReturnId(string $sql, string $types = "", array $params = []): int {
        $stmt = $this->db->prepare($sql);
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $this->db->insert_id;
    }

    // sanitize input to prevent SQL injection
    public function getSanitizedInput(string $input): string {
        return $this->db->real_escape_string($input);
    }

    // Update data. Data must be an associative array: ['column' => 'value']
    public function update(int $id, array $data): bool {
        $setColumns = implode(", ", array_map(fn($col) => "$col = ?", array_keys($data)));
        $sql = "UPDATE {$this->tableName} SET {$setColumns} WHERE id = ?;";
        $stmt = $this->db->prepare($sql);

        $types = $this->detectTypes(array_values($data)) . "i";
        $values = array_values($data);
        $values[] = $id;
        $stmt->bind_param($types, ...$values);

        return $stmt->execute();
    }

    // Detect types 
    private function detectTypes(array $data): string {
        $types = "";

        foreach ($data as $value) {
            // null -> treat as string for MySQL (NULL handled separately if needed)
            if ($value === null) {
                $types .= "s";
                continue;
            }

            // Already an integer
            if (is_int($value)) {
                $types .= "i";
                continue;
            }

            // Already a float
            if (is_float($value)) {
                $types .= "d";
                continue;
            }

            // Numeric string integer ("12", "-12", "0002")
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                $types .= "i";
                continue;
            }

            // Numeric decimal ("12.5", "-0.5", ".5", "5.")
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
                $types .= "d";
                continue;
            }

            // fallback: everything else is string
            $types .= "s";
        }

        return $types;
    }

    // get total count of records
    public function getCountWithWhereClause(string $mainTableAlias, string $whereClause, string $joinClause = ''): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} {$mainTableAlias} {$joinClause} {$whereClause};";
        $result = $this->rawQuery($sql);
        return (int) ($result[0]['total'] ?? 0);    
    }
}