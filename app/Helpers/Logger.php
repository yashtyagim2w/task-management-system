<?php
namespace App\Helpers;

use Throwable;

class Logger {

    public static function error(Throwable $exception): void {
        $logMessage = sprintf(
            "[%s] %s in %s on line %d%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            PHP_EOL
        );

        error_log($logMessage);
    }
    
}