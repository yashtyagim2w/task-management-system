<?php

namespace App\Core;

abstract class Controller {

    protected function render(string $view, array $data = []){
        $viewPath = VIEWS_PATH . $view . ".php";
        $headerComponentPath = COMPONENTS_PATH . '/header.php';
        $sidebarComponentPath = COMPONENTS_PATH . '/sidebar.php';
        $footerComponentPath = COMPONENTS_PATH . '/footer.php';
        $currentURI = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if(!file_exists($viewPath)) {
            exit("View doesn't exist");
        }

        extract($data);
        
        require $headerComponentPath;
        if(!($public_page ?? false)) {
            require $sidebarComponentPath;
            echo sideBar($currentURI);   
        }
        require $viewPath;
        require $footerComponentPath;
    }

}