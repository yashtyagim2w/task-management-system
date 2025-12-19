<?php
namespace App\Helpers;

class Session {
    
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($_ENV['UNIQUE_SESSION_NAME'] ?? 'MY_APP_SESSION');
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default; 
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        session_destroy();
    }
    
}