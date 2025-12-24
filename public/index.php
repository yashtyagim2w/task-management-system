<?php

use App\Controllers\AuthController;
use App\Core\Router;
use App\Helpers\Session;

require dirname(__DIR__) . '/app/Config/bootstrap.php';

Session::start();

$router = new Router();

$routes = [
    'GET' => [
        '/login' => [AuthController::class, 'renderLoginForm'],
    ],
    
    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/logout' => [AuthController::class, 'logout'],
    ],

    'PATCH' => [

    ],
];

foreach($routes as $method => $paths) {
    foreach($paths as $path => $handler) {
        $router->{strtolower($method)}($path, $handler);
    }
}

$router->dispatch();