<?php

namespace App\Controllers\Employee;

use App\Core\Controller;
use App\Helpers\Auth;

abstract class EmployeeController extends Controller
{

    public function __construct()
    {
        Auth::requireLogin();
        Auth::employeeOnly();
    }
}
