<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Helpers\Auth;

abstract class AdminController extends Controller {

    public function __construct() {
        Auth::requireLogin();
        Auth::adminOnly();
    }

}