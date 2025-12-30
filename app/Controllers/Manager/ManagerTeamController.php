<?php

namespace App\Controllers\Manager;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\ManagerTeam;
use App\Models\Users;
use Throwable;

class ManagerTeamController extends ManagerController
{

    /**
     * Render team management page
     */
    public function renderTeamPage(): void
    {
        $data = [
            "header_title" => "My Team",
        ];

        $teamModel = new ManagerTeam();
        $data['unassignedEmployees'] = $teamModel->getUnassignedEmployees();

        $this->render("/manager/team", $data);
    }

    /**
     * Get team members paginated (API)
     */
    public function getTeamMembersPaginated(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $search = $_GET['search'] ?? '';
            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $teamModel = new ManagerTeam();
            $response = $teamModel->getTeamMembers($managerId, $search, $limit, $offset);
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Team members fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching team members.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add employee to team (API)
     */
    public function addTeamMember(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $employeeId = (int)($_POST['employee_id'] ?? 0);

            if ($employeeId <= 0) {
                $this->failure("Please select a valid employee.", [], HTTP_BAD_REQUEST);
            }

            // Check if employee exists and is actually an employee
            $userModel = new Users();
            $employee = $userModel->getUserDetailsById($employeeId);

            if (empty($employee) || $employee[0]['role_id'] != 3) {
                $this->failure("Invalid employee selected.", [], HTTP_BAD_REQUEST);
            }

            $teamModel = new ManagerTeam();

            // Check if employee is already assigned to any manager
            if ($teamModel->isEmployeeAssigned($employeeId)) {
                $currentManager = $teamModel->getEmployeeManager($employeeId);
                $managerName = $currentManager ? $currentManager['first_name'] . ' ' . $currentManager['last_name'] : 'another manager';
                $this->failure("This employee is already assigned to {$managerName}. Please contact admin to reassign.", [], HTTP_BAD_REQUEST);
            }

            // Add to team
            $success = $teamModel->addMember($managerId, $employeeId);

            if ($success) {
                $this->success("Employee added to your team successfully.", [], HTTP_CREATED);
            }

            $this->failure("Failed to add employee to team. Please try again.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while adding team member.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove employee from team (API)
     */
    public function removeTeamMember(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)Session::get('userId');
            $employeeId = (int)($_POST['employee_id'] ?? 0);

            if ($employeeId <= 0) {
                $this->failure("Invalid employee ID.", [], HTTP_BAD_REQUEST);
            }

            $teamModel = new ManagerTeam();

            // Check if employee is in this manager's team
            if (!$teamModel->isInManagerTeam($managerId, $employeeId)) {
                $this->failure("This employee is not in your team.", [], HTTP_BAD_REQUEST);
            }

            $success = $teamModel->removeMember($managerId, $employeeId);

            if ($success) {
                $this->success("Employee removed from your team.");
            }

            $this->failure("Failed to remove employee. Please try again.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while removing team member.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get unassigned employees (API)
     */
    public function getUnassignedEmployees(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $teamModel = new ManagerTeam();
            $employees = $teamModel->getUnassignedEmployees();

            $this->success("Unassigned employees fetched.", ['employees' => $employees]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
