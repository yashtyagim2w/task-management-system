<?php
namespace App\Core;

use mysqli;

class Service {

    protected mysqli $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function begin(): void {
        $this->db->begin_transaction();
    }

    public function commit(): void {
        $this->db->commit();
    }

    public function rollback(): void {
        $this->db->rollback();
    }

    private function response(bool $isSuccess, string $message = '', array $data = []): array {
        return [
            'success' => $isSuccess,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function success(string $message, array $data = []): array {
        return $this->response(true, $message, $data);
    }

    protected function failure(string $message, array $data = []): array {
        return $this->response(false, $message, $data);
    }
    
}   