<?php

use App\Core\Router;
use App\Helpers\Session;

require dirname(__DIR__) . '/app/Config/bootstrap.php';

Session::start();

$router = new Router();

$routes = [
    'GET' => [

    ],

    'POST' => [

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