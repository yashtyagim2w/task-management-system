<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;

class HomeController extends Controller {

    public function index(): void {
        Auth::redirectIfLoggedIn();
        Auth::requireLogin();
    }
    
}