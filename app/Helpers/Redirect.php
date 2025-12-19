<?php
namespace App\Helpers;

class Redirect {

    public static function to(string $path): void {
        header("Location: {$path}");
        exit;
    }

    public static function redirectWithMessage(string $path, string $key, string $message){
        if (!empty($key) && !empty($message)) {
            Session::set($key, $message);
        }
        self::to($path);
    }
    
}