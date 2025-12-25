<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;

class ErrorController extends Controller {

    public function notFound(): void {
        http_response_code(404);
        $isLoggedIn = Auth::isLoggedIn();
        $public_page = !$isLoggedIn;
        $data = [
            'header_title' => '404 | Page Not Found',
            'public_page' => $public_page,
        ];

        $this->render("/404", $data);
    }

    public function unauthorized(): void {
        Auth::requireLogin();

        $data = [
            "header_title" => "Unauthorized Access"
        ];
        $this->render("/unauthorized", $data);
    }

}