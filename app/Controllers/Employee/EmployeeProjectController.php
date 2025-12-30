<?php

namespace App\Controllers\Employee;

use App\Helpers\Logger;
use App\Helpers\Session;
use App\Models\Projects;
use Throwable;

class EmployeeProjectController extends EmployeeController
{

    /**
     * Render assigned projects page
     */
    public function renderProjectsPage(): void
    {
        $projectModel = new Projects();

        $data = [
            "header_title" => "My Projects",
            "statuses" => $projectModel->getStatuses(),
        ];

        $this->render("/employee/projects", $data);
    }

    /**
     * Get assigned projects paginated (API)
     */
    public function getProjectsPaginated(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->failure("Invalid request method.", [], HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $userId = (int)Session::get('userId');
            $search = $_GET['search'] ?? '';
            $statusFilter = isset($_GET['status_id']) && $_GET['status_id'] !== '' ? (int)$_GET['status_id'] : null;

            // Sorting
            $allowedSortColumns = ['name', 'created_at', 'project_status_id', 'task_count'];
            ['sort_by' => $sort_by, 'sort_order' => $sort_order] = $this->getSortParams($allowedSortColumns, "created_at");

            if ($sort_by === 'task_count') {
                $sortColumn = "task_count {$sort_order}";
            } else {
                $sortColumn = "p.{$sort_by} {$sort_order}";
            }

            ['page' => $page, 'limit' => $limit, 'offset' => $offset] = $this->getPaginationParams();

            $projectModel = new Projects();
            $response = $projectModel->getByAssignedUser($userId, $search, $statusFilter, $sortColumn, $limit, $offset);
            $structuredResponse = $this->paginatedResponse($response['data'], $page, $limit, $response['total_count']);

            $this->success("Projects fetched successfully.", $structuredResponse);
        } catch (Throwable $e) {
            Logger::error($e);
            $this->failure("An error occurred while fetching projects.", [], HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
