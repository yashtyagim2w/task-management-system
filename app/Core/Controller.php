<?php

namespace App\Core;

abstract class Controller {

    protected function render(string $view, array $data = []){
        $viewPath = VIEWS_PATH . $view . ".php";
        $headerComponentPath = COMPONENTS_PATH . '/header.php';
        $footerComponentPath = COMPONENTS_PATH . '/footer.php';
        
        if(!file_exists($viewPath)) {
            exit("View doesn't exist");
        }

        extract($data);
        require $headerComponentPath;
        require $viewPath;
        require $footerComponentPath;
    }

}