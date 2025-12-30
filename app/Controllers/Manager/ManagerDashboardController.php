<?php

namespace App\Controllers\Manager;

use App\Helpers\Session;

class ManagerDashboardController extends ManagerController {

    public function renderDashboard(): void {
        $data = [
            "header_title" => "Manager Dashboard",
        ];

        $this->render("/manager/dashboard", $data);
    }
}
