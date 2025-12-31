<?php
namespace App\Controllers\Admin;

use App\Models\Users;
use App\Models\Projects;
use App\Models\ProjectTasks;
use App\Models\TaskActivityLog;

class AdminDashboardController extends AdminController {

    public function renderDashboard(): void {
        $usersModel = new Users();
        $projectsModel = new Projects();
        $tasksModel = new ProjectTasks();
        $activityModel = new TaskActivityLog();

        $data = [
            "header_title" => "Admin Dashboard",
            "userStats" => $usersModel->getDashboardStats(),
            "projectStats" => $projectsModel->getDashboardStats(),
            "taskStats" => $tasksModel->getDashboardStats(),
            "recentActivities" => $activityModel->getRecentActivities(null, 10)
        ];
        $this->render("/admin/dashboard", $data);
    }
}