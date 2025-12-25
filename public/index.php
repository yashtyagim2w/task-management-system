<?php

use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ErrorController;
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
        '/unauthorized' => [ErrorController::class, 'unauthorized'],

        // Admin Routes

        // dasboard
        '/admin/dashboard' => [AdminDashboardController::class, 'renderDashboard'],

        // user management
        '/admin/users' => [AdminUserController::class, 'renderAllUsers'],
        '/api/admin/users' => [AdminUserController::class, 'getUsersPaginated'],
        
        // todo
        // // Manager Routes
        // '/manager/dashboard' => [ManagerController::class, 'managerDashboard'],
        
        // // Employee Routes
        // '/employee/dashboard' => [EmployeeController::class, 'employeeDashboard'],
    ],
    
    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/logout' => [AuthController::class, 'logout'],
        
        // Admin Routes
        
        // user management
        '/admin/user/create' => [AdminUserController::class, 'createNewUser'],
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