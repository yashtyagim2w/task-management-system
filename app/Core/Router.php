<?php

namespace App\Core;

use App\Controllers\ErrorController;

class Router {

    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void {
        $this->routes['POST'][$path] = $handler;
    }

    public function patch(string $path, callable|array $handler): void {
        $this->routes['PATCH'][$path] = $handler;
    }

    public function delete(string $path, callable|array $handler): void {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function dispatch(): void {
        // GET, POST -> method
        $method = $_SERVER['REQUEST_METHOD'];

        // path
        $uri = strtok($_SERVER['REQUEST_URI'], '?');

        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];

            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = new $class;
                $controller->$method();
                return;
            }

            $handler();
            return;
        }

        // If no route matched, show 404 error
        $ErrorController = new ErrorController();
        $ErrorController->notFound();
    }
}