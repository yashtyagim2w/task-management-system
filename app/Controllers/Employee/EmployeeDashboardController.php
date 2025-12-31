<?php

namespace App\Controllers\Employee;

use App\Helpers\Session;
use App\Models\ProjectTasks;
use App\Models\Projects;

class EmployeeDashboardController extends EmployeeController
{

    public function renderDashboard(): void
    {
        $userId = (int)Session::get('userId');
        $taskModel = new ProjectTasks();
        $projectModel = new Projects();

        $data = [
            "header_title" => "Employee Dashboard",
            "taskStats" => $taskModel->getEmployeeDashboardStats($userId),
            "projectCount" => $projectModel->getEmployeeProjectCount($userId),
            "dueSoonTasks" => $taskModel->getDueSoon($userId, 5),
        ];

        $this->render("/employee/dashboard", $data);
    }
}
