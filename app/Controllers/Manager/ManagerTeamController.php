<?php

namespace App\Controllers\Manager;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\ManagerTeam;
use App\Models\Users;
use App\Services\EmailService;
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
            $activeStatus = isset($_GET['active_status']) ? (($_GET['active_status'] === '') ? null : (int)$_GET['active_status']) : 1;
            $sortBy = $_GET['sort_by'] ?? 'assigned_at';
            $sortOrder = $_GET['sort_order'] ?? 'DESC';
            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $teamModel = new ManagerTeam();
            $response = $teamModel->getTeamMembers($managerId, $search, $activeStatus, $sortBy, $sortOrder, $limit, $offset);
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
                // Send team added email to employee
                try {
                    $managerDetails = $userModel->getUserDetailsById($managerId)[0];
                    $emailService = new EmailService();
                    $emailService->sendTemplateMail(
                        $employee[0]['email'],
                        $employee[0]['first_name'] . ' ' . $employee[0]['last_name'],
                        'You have been added to a team',
                        'team_added',
                        [
                            'employeeName' => $employee[0]['first_name'] . ' ' . $employee[0]['last_name'],
                            'managerName' => $managerDetails['first_name'] . ' ' . $managerDetails['last_name'],
                            'managerEmail' => $managerDetails['email']
                        ]
                    );
                } catch (Throwable $emailError) {
                    Logger::error($emailError);
                }

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

            // Get employee details before removal
            $userModel = new Users();
            $employee = $userModel->getUserDetailsById($employeeId);
            $manager = $userModel->getUserDetailsById($managerId);

            $success = $teamModel->removeMember($managerId, $employeeId);

            if ($success) {
                // Send team removed email to employee
                if ($employee && $manager) {
                    try {
                        $emailService = new EmailService();
                        $emailService->sendTemplateMail(
                            $employee[0]['email'],
                            $employee[0]['first_name'] . ' ' . $employee[0]['last_name'],
                            'You have been removed from a team',
                            'team_removed',
                            [
                                'employeeName' => $employee[0]['first_name'] . ' ' . $employee[0]['last_name'],
                                'managerName' => $manager[0]['first_name'] . ' ' . $manager[0]['last_name'],
                                'managerEmail' => $manager[0]['email']
                            ]
                        );
                    } catch (Throwable $emailError) {
                        Logger::error($emailError);
                    }
                }

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
