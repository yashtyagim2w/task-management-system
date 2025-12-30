<?php

use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminProjectController;
use App\Controllers\Admin\AdminTaskController;
use App\Controllers\Admin\AdminTeamAssignmentController;
use App\Controllers\Admin\AdminTeamViewController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\Manager\ManagerDashboardController;
use App\Controllers\Manager\ManagerProjectController;
use App\Controllers\Manager\ManagerTaskController;
use App\Controllers\Manager\ManagerTeamController;
use App\Controllers\Employee\EmployeeDashboardController;
use App\Controllers\Employee\EmployeeProjectController;
use App\Controllers\Employee\EmployeeTaskController;
use App\Controllers\AuthController;
use App\Controllers\ErrorController;
use App\Controllers\HomeController;
use App\Core\Router;
use App\Helpers\Session;

require dirname(__DIR__) . '/app/Config/bootstrap.php';

Session::start();

$router = new Router();

$routes = [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/login' => [AuthController::class, 'renderLoginForm'],
        '/unauthorized' => [ErrorController::class, 'unauthorized'],

        // =====================
        // Admin Routes
        // =====================

        // Dashboard
        '/admin/dashboard' => [AdminDashboardController::class, 'renderDashboard'],

        // User management
        '/admin/users' => [AdminUserController::class, 'renderAllUsers'],
        '/api/admin/managers' => [AdminUserController::class, 'getAllManagers'],
        '/api/admin/users' => [AdminUserController::class, 'getUsersPaginated'],

        // Team Assignments
        '/admin/team-assignments' => [AdminTeamAssignmentController::class, 'renderTeamAssignmentsPage'],
        '/api/admin/team-assignments' => [AdminTeamAssignmentController::class, 'getTeamAssignmentsPaginated'],
        '/api/admin/team-assignments/managers' => [AdminTeamAssignmentController::class, 'getAllManagers'],
        '/api/admin/unassigned-employees' => [AdminTeamAssignmentController::class, 'getUnassignedEmployees'],


        // View Teams
        '/admin/view-team' => [AdminTeamViewController::class, 'renderViewTeamPage'],
        '/api/admin/view-team/managers' => [AdminTeamViewController::class, 'getManagersWithStats'],
        '/api/admin/view-team/unassigned-employees' => [AdminTeamViewController::class, 'getUnassignedEmployeesForManager'],
        '/api/admin/view-team/team-members' => [AdminTeamViewController::class, 'getManagerTeamMembers'],

        // Projects
        '/admin/projects' => [AdminProjectController::class, 'renderProjectsPage'],
        '/api/admin/projects' => [AdminProjectController::class, 'getProjectsPaginated'],
        '/api/admin/project/details' => [AdminProjectController::class, 'getProjectDetails'],
        '/api/admin/project/assignees' => [AdminProjectController::class, 'getProjectAssignees'],
        '/api/admin/project/assignable-employees' => [AdminProjectController::class, 'getAssignableEmployees'],

        // Tasks
        '/admin/tasks' => [AdminTaskController::class, 'renderTasksPage'],
        '/api/admin/tasks' => [AdminTaskController::class, 'getTasksPaginated'],
        '/api/admin/tasks/kanban' => [AdminTaskController::class, 'getTasksForKanban'],
        '/api/admin/task/details' => [AdminTaskController::class, 'getTaskDetails'],
        '/api/admin/task/comments' => [AdminTaskController::class, 'getTaskComments'],
        '/api/admin/task/activity-logs' => [AdminTaskController::class, 'getTaskActivityLogs'],
        '/api/admin/project/activity-logs' => [AdminTaskController::class, 'getProjectActivityLogs'],
        '/api/admin/task/assignees' => [AdminTaskController::class, 'getProjectAssignees'],

        // =====================
        // Manager Routes
        // =====================

        // Dashboard
        '/manager/dashboard' => [ManagerDashboardController::class, 'renderDashboard'],

        // Team management
        '/manager/team' => [ManagerTeamController::class, 'renderTeamPage'],
        '/api/manager/team' => [ManagerTeamController::class, 'getTeamMembersPaginated'],
        '/api/manager/unassigned-employees' => [ManagerTeamController::class, 'getUnassignedEmployees'],

        // Projects
        '/manager/projects' => [ManagerProjectController::class, 'renderProjectsPage'],
        '/api/manager/projects' => [ManagerProjectController::class, 'getProjectsPaginated'],
        '/api/manager/project/details' => [ManagerProjectController::class, 'getProjectDetails'],
        '/api/manager/project/assignees' => [ManagerProjectController::class, 'getProjectAssignees'],
        '/api/manager/project/assignable-employees' => [ManagerProjectController::class, 'getAssignableEmployees'],

        // Tasks
        '/manager/tasks' => [ManagerTaskController::class, 'renderTasksPage'],
        '/api/manager/tasks' => [ManagerTaskController::class, 'getTasksPaginated'],
        '/api/manager/tasks/kanban' => [ManagerTaskController::class, 'getTasksForKanban'],
        '/api/manager/task/details' => [ManagerTaskController::class, 'getTaskDetails'],
        '/api/manager/task/comments' => [ManagerTaskController::class, 'getTaskComments'],
        '/api/manager/task/activity-logs' => [ManagerTaskController::class, 'getTaskActivityLogs'],
        '/api/manager/project/activity-logs' => [ManagerTaskController::class, 'getProjectActivityLogs'],
        '/api/manager/task/assignees' => [ManagerTaskController::class, 'getProjectAssignees'],

        // =====================
        // Employee Routes
        // =====================

        // Dashboard
        '/employee/dashboard' => [EmployeeDashboardController::class, 'renderDashboard'],

        // Projects
        '/employee/projects' => [EmployeeProjectController::class, 'renderProjectsPage'],
        '/api/employee/projects' => [EmployeeProjectController::class, 'getProjectsPaginated'],

        // Tasks
        '/employee/tasks' => [EmployeeTaskController::class, 'renderTasksPage'],
        '/api/employee/tasks' => [EmployeeTaskController::class, 'getTasksPaginated'],
        '/api/employee/tasks/kanban' => [EmployeeTaskController::class, 'getTasksForKanban'],
        '/api/employee/task/details' => [EmployeeTaskController::class, 'getTaskDetails'],
        '/api/employee/task/comments' => [EmployeeTaskController::class, 'getTaskComments'],
        '/api/employee/task/activity-logs' => [EmployeeTaskController::class, 'getTaskActivityLogs'],
    ],

    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/logout' => [AuthController::class, 'logout'],

        // =====================
        // Admin Routes
        // =====================

        // User management
        '/admin/user/create' => [AdminUserController::class, 'createNewUser'],

        // Team Assignments
        '/api/admin/team/assign' => [AdminTeamAssignmentController::class, 'assignEmployeeToManager'],
        '/api/admin/team/remove' => [AdminTeamAssignmentController::class, 'removeEmployeeFromManager'],

        // View Teams
        '/api/admin/view-team/assign' => [AdminTeamViewController::class, 'assignEmployeeToManager'],

        // Projects
        '/admin/project/create' => [AdminProjectController::class, 'createProject'],
        '/api/admin/project/assign-user' => [AdminProjectController::class, 'assignUserToProject'],
        '/api/admin/project/remove-user' => [AdminProjectController::class, 'removeUserFromProject'],
        '/api/admin/project/recover' => [AdminProjectController::class, 'recoverProject'],

        // Tasks
        '/admin/task/create' => [AdminTaskController::class, 'createTask'],
        '/api/admin/task/comment' => [AdminTaskController::class, 'addTaskComment'],

        // =====================
        // Manager Routes
        // =====================

        // Team management
        '/manager/team/add' => [ManagerTeamController::class, 'addTeamMember'],
        '/manager/team/remove' => [ManagerTeamController::class, 'removeTeamMember'],

        // Projects
        '/manager/project/create' => [ManagerProjectController::class, 'createProject'],
        '/api/manager/project/assign-user' => [ManagerProjectController::class, 'assignUserToProject'],
        '/api/manager/project/remove-user' => [ManagerProjectController::class, 'removeUserFromProject'],

        // Tasks
        '/manager/task/create' => [ManagerTaskController::class, 'createTask'],
        '/api/manager/task/comment' => [ManagerTaskController::class, 'addTaskComment'],

        // =====================
        // Employee Routes
        // =====================

        // Tasks
        '/api/employee/task/comment' => [EmployeeTaskController::class, 'addTaskComment'],
    ],

    'PATCH' => [
        // Admin 
        '/api/admin/user' => [AdminUserController::class, 'updateUserDetails'],
        '/api/admin/project' => [AdminProjectController::class, 'updateProject'],
        '/api/admin/task' => [AdminTaskController::class, 'updateTask'],

        // Manager
        '/api/manager/project' => [ManagerProjectController::class, 'updateProject'],
        '/api/manager/task' => [ManagerTaskController::class, 'updateTask'],

        // Employee
        '/api/employee/task/status' => [EmployeeTaskController::class, 'updateTaskStatus'],

        // Admin task status (drag-drop)
        '/api/admin/task/status' => [AdminTaskController::class, 'updateTaskStatus'],

        // Manager task status (drag-drop)
        '/api/manager/task/status' => [ManagerTaskController::class, 'updateTaskStatus'],
    ],

    'DELETE' => [
        // Admin
        '/api/admin/project' => [AdminProjectController::class, 'deleteProject'],
        '/api/admin/task' => [AdminTaskController::class, 'deleteTask'],

        // Manager
        '/api/manager/project' => [ManagerProjectController::class, 'deleteProject'],
        '/api/manager/task' => [ManagerTaskController::class, 'deleteTask'],
    ],
];

foreach ($routes as $method => $paths) {
    foreach ($paths as $path => $handler) {
        $router->{strtolower($method)}($path, $handler);
    }
}

$router->dispatch();
