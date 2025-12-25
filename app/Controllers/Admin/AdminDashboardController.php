<?php
namespace App\Controllers\Admin;

class AdminDashboardController extends AdminController {

    public function renderDashboard(): void {
        $data = [
            "header_title" => "Admin Dashboard"
        ];
        $this->render("/admin/dashboard", $data);
    }

}