<?php

namespace App\Controllers\Admin;

use App\Helpers\Logger;
use App\Models\ManagerTeam;
use Throwable;

class AdminTeamViewController extends AdminController
{
    /**
     * Render the view team page
     */
    public function renderViewTeamPage(): void
    {
        $data = [
            "header_title" => "View Teams",
        ];

        $this->render("/admin/view-team", $data);
    }

    /**
     * Get all managers with their statistics (API)
     */
    public function getManagersWithStats(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $search = $_GET['search'] ?? '';

            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $managerTeamModel = new ManagerTeam();
            $response = $managerTeamModel->getAllManagersWithStats($search, $limit, $offset);

            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Managers fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching managers.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get unassigned employees for a specific manager (API)
     */
    public function getUnassignedEmployeesForManager(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)($_GET['manager_id'] ?? 0);

            if ($managerId <= 0) {
                $this->failure("Invalid manager ID.", [], HTTP_BAD_REQUEST);
            }

            $managerTeamModel = new ManagerTeam();
            $employees = $managerTeamModel->getUnassignedEmployeesForManager($managerId);

            $this->success("Unassigned employees fetched successfully.", $employees);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching employees.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign employee to manager (API)
     */
    public function assignEmployeeToManager(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)($_POST['manager_id'] ?? 0);
            $employeeId = (int)($_POST['employee_id'] ?? 0);

            if ($managerId <= 0) {
                $this->failure("Invalid manager ID.", [], HTTP_BAD_REQUEST);
            }

            if ($employeeId <= 0) {
                $this->failure("Invalid employee ID.", [], HTTP_BAD_REQUEST);
            }

            $managerTeamModel = new ManagerTeam();

            // Check if employee is already assigned to this manager
            if ($managerTeamModel->isInManagerTeam($managerId, $employeeId)) {
                $this->failure("Employee is already assigned to this manager.", [], HTTP_BAD_REQUEST);
            }

            // Assign employee to manager (will remove from other teams if exists)
            $assigned = $managerTeamModel->adminAssignEmployee($managerId, $employeeId);

            if ($assigned) {
                $this->success("Employee assigned successfully.");
            }

            $this->failure("Failed to assign employee.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while assigning employee.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get team members for a specific manager (API)
     */
    public function getManagerTeamMembers(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)($_GET['manager_id'] ?? 0);
            $search = $_GET['search'] ?? '';

            if ($managerId <= 0) {
                $this->failure("Invalid manager ID.", [], HTTP_BAD_REQUEST);
            }

            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $managerTeamModel = new ManagerTeam();
            $response = $managerTeamModel->getTeamMembers($managerId, $search, $limit, $offset);

            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Team members fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching team members.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
