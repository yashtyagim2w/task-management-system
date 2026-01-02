<?php

namespace App\Controllers\Admin;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\ManagerTeam;
use App\Models\Users;
use App\Services\EmailService;
use Throwable;

class AdminTeamAssignmentController extends AdminController
{

    /**
     * Render team assignments page
     */
    public function renderTeamAssignmentsPage(): void
    {
        $data = [
            "header_title" => "Team Assignments",
        ];

        $this->render("/admin/team-assignments", $data);
    }

    /**
     * Get all team assignments paginated (API)
     */
    public function getTeamAssignmentsPaginated(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $search = $_GET['search'] ?? '';
            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $teamModel = new ManagerTeam();
            $response = $teamModel->getAllTeamAssignments($search, $limit, $offset);
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Team assignments fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching team assignments.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign employee to manager (API) - Admin can force assign
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
                $this->failure("Please select a manager.", [], HTTP_BAD_REQUEST);
            }
            if ($employeeId <= 0) {
                $this->failure("Please select an employee.", [], HTTP_BAD_REQUEST);
            }

            // Validate manager exists and is a manager
            $userModel = new Users();
            $manager = $userModel->getUserDetailsById($managerId);
            if (empty($manager) || $manager[0]['role_id'] != 2) {
                $this->failure("Invalid manager selected.", [], HTTP_BAD_REQUEST);
            }

            // Validate employee exists and is an employee
            $employee = $userModel->getUserDetailsById($employeeId);
            if (empty($employee) || $employee[0]['role_id'] != 3) {
                $this->failure("Invalid employee selected.", [], HTTP_BAD_REQUEST);
            }

            $teamModel = new ManagerTeam();

            // Admin can force assign (removes from any existing team)
            $success = $teamModel->adminAssignEmployee($managerId, $employeeId);

            if ($success) {
                // Send team added email to employee
                try {
                    $emailService = new EmailService();
                    $emailService->sendTemplateMail(
                        $employee[0]['email'],
                        $employee[0]['first_name'] . ' ' . $employee[0]['last_name'],
                        'You have been added to a team',
                        'team_added',
                        [
                            'employeeName' => $employee[0]['first_name'] . ' ' . $employee[0]['last_name'],
                            'managerName' => $manager[0]['first_name'] . ' ' . $manager[0]['last_name'],
                            'managerEmail' => $manager[0]['email']
                        ]
                    );
                } catch (Throwable $emailError) {
                    Logger::error($emailError);
                }

                $this->success("Employee assigned to manager successfully.", [], HTTP_CREATED);
            }

            $this->failure("Failed to assign employee. Please try again.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while assigning employee.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove employee from manager's team (API)
     */
    public function removeEmployeeFromManager(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $managerId = (int)($_POST['manager_id'] ?? 0);
            $employeeId = (int)($_POST['employee_id'] ?? 0);

            if ($managerId <= 0 || $employeeId <= 0) {
                $this->failure("Invalid manager or employee ID.", [], HTTP_BAD_REQUEST);
            }

            $teamModel = new ManagerTeam();

            // Get employee and manager details before removal
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

                $this->success("Employee removed from team successfully.");
            }

            $this->failure("Failed to remove employee.", [], HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while removing employee.", [], HTTP_INTERNAL_SERVER_ERROR);
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

    /**
     * Get all managers (API) - for dynamic dropdown
     */
    public function getAllManagers(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $userModel = new Users();
            $currentUserId = Session::get("userId");

            // Get all active managers without pagination
            $managers = $userModel->getAllUserPaginated('', 'u.first_name ASC', 2, null, $currentUserId, 1000, 0);

            $this->success("Managers fetched successfully.", ['managers' => $managers['data']]);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching managers.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
