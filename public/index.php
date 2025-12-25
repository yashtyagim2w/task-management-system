<?php

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Core\Router;
use App\Helpers\Session;

require dirname(__DIR__) . '/app/Config/bootstrap.php';

Session::start();

$router = new Router();

$routes = [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/login' => [AuthController::class, 'renderLoginForm'],

        // Todo 
        // // Admin Routes
        // '/admin/dashboard' => [AdminController::class, 'adminDashboard'],

        // // Manager Routes
        // '/manager/dashboard' => [ManagerController::class, 'managerDashboard'],

        // // Employee Routes
        // '/employee/dashboard' => [EmployeeController::class, 'employeeDashboard'],
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