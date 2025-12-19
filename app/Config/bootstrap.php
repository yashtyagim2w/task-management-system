<?php

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(dirname(__DIR__)));
$dotenv->load();

$required = [
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

require dirname(__DIR__) . '/Config/constants.php';