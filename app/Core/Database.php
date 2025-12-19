<?php
namespace App\Core;

use mysqli;
use mysqli_sql_exception;
use RuntimeException;

class Database {

    private static ?mysqli $connection = null;

    public static function getConnection(): mysqli {
        if(self::$connection !== null){
            return self::$connection;
        }

        // Load env variables 
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $name = $_ENV['DB_NAME'] ?? '';

        // Enable mysqli exceptions instead of warnings
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli($host, $user, $pass, $name);
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        self::$connection = $conn;
        return self::$connection;
    }
}