<?php

namespace App\Controllers\Manager;

use App\Helpers\Session;
use App\Models\Projects;
use App\Models\ProjectTasks;
use App\Models\TaskActivityLog;
use App\Models\ManagerTeam;

class ManagerDashboardController extends ManagerController {

    public function renderDashboard(): void {
        $userId = (int)Session::get('userId');

        $projectsModel = new Projects();
        $tasksModel = new ProjectTasks();
        $activityModel = new TaskActivityLog();
        $teamModel = new ManagerTeam();

        $data = [
            "header_title" => "Manager Dashboard",
            "teamSize" => $teamModel->getTeamMemberCount($userId),
            "projectStats" => $projectsModel->getDashboardStats($userId),
            "taskStats" => $tasksModel->getDashboardStats($userId),
            "recentActivities" => $activityModel->getRecentActivities($userId, 10)
        ];

        $this->render("/manager/dashboard", $data);
    }
}
