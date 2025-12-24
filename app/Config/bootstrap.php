<?php

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(dirname(__DIR__)));
$dotenv->load();

$required = [
    'APP_ENV',
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'UNIQUE_SESSION_NAME'
];

foreach ($required as $key) {
    if (empty($_ENV[$key])) {
        throw new RuntimeException("Missing env variable: {$key}");
    }
}

$isDev = $_ENV['APP_ENV'] === 'development';
error_reporting(E_ALL);
if ($isDev) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

require dirname(__DIR__) . '/Config/constants.php';