<?php

namespace App\Controllers\Manager;

use App\Core\Controller;
use App\Helpers\Auth;

abstract class ManagerController extends Controller {

    public function __construct() {
        Auth::requireLogin();
        Auth::managerOnly();
    }
}
