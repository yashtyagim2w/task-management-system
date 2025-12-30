<?php

namespace App\Controllers\Employee;

use App\Helpers\Session;
use App\Models\ProjectTasks;

class EmployeeDashboardController extends EmployeeController
{

    public function renderDashboard(): void
    {
        $userId = (int)Session::get('userId');
        $taskModel = new ProjectTasks();

        $data = [
            "header_title" => "Employee Dashboard",
            "dueSoonTasks" => $taskModel->getDueSoon($userId, 5),
        ];

        $this->render("/employee/dashboard", $data);
    }
}
