<?php
namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller {

    public function notFound(): void {
        http_response_code(404);
        
        $data = [
            'header_title' => '404 | Page Not Found'
        ];

        $this->render("/404", $data);
    }

}