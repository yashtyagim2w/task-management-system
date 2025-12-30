<?php

namespace App\Models;

use App\Core\Model;

class ProjectActivityLog extends Model
{
    protected string $tableName = "project_task_activity_logs";

    /**
     * Log a project/task activity
     * @param int $projectId - Required project ID
     * @param int|null $taskId - Optional task ID (null for project-level activities)
     * @param int|null $userId - User who performed the action
     * @param string $action - Action type (created, updated, status_changed, assigned, etc.)
     * @param array|null $details - Additional details (old/new values)
     */
    public function logActivity(int $projectId, ?int $taskId, ?int $userId, string $action, ?array $details = null): bool
    {
        $detailsJson = $details ? json_encode($details) : null;

        $sql = "INSERT INTO {$this->tableName} (project_id, task_id, user_id, action, details) 
                VALUES (?, ?, ?, ?, ?)";

        return $this->rawExecute($sql, "iiiss", [$projectId, $taskId, $userId, $action, $detailsJson]);
    }

    /**
     * Get activity logs for a project (project-level only, task_id IS NULL)
     */
    public function getProjectLogs(int $projectId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
            pal.id,
            pal.project_id,
            pal.task_id,
            pal.user_id,
            pal.action,
            pal.details,
            pal.created_at,
            u.first_name,
            u.last_name,
            u.email
        FROM {$this->tableName} pal
        LEFT JOIN users u ON pal.user_id = u.id
        WHERE pal.project_id = ? AND pal.task_id IS NULL
        ORDER BY pal.created_at DESC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$projectId, $limit, $offset]);

        // Parse JSON details
        foreach ($data as &$log) {
            if ($log['details']) {
                $log['details'] = json_decode($log['details'], true);
            }
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE project_id = ? AND task_id IS NULL";
        $countResult = $this->rawQuery($countSql, "i", [$projectId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get activity logs for a specific task
     */
    public function getTaskLogs(int $taskId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
            pal.id,
            pal.project_id,
            pal.task_id,
            pal.user_id,
            pal.action,
            pal.details,
            pal.created_at,
            u.first_name,
            u.last_name,
            u.email
        FROM {$this->tableName} pal
        LEFT JOIN users u ON pal.user_id = u.id
        WHERE pal.task_id = ?
        ORDER BY pal.created_at DESC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$taskId, $limit, $offset]);

        foreach ($data as &$log) {
            if ($log['details']) {
                $log['details'] = json_decode($log['details'], true);
            }
        }

        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE task_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$taskId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get all activity logs for a project (including task activities)
     */
    public function getAllProjectActivityLogs(int $projectId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
            pal.id,
            pal.project_id,
            pal.task_id,
            pal.user_id,
            pal.action,
            pal.details,
            pal.created_at,
            u.first_name,
            u.last_name,
            u.email,
            pt.name as task_name
        FROM {$this->tableName} pal
        LEFT JOIN users u ON pal.user_id = u.id
        LEFT JOIN project_tasks pt ON pal.task_id = pt.id
        WHERE pal.project_id = ?
        ORDER BY pal.created_at DESC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$projectId, $limit, $offset]);

        foreach ($data as &$log) {
            if ($log['details']) {
                $log['details'] = json_decode($log['details'], true);
            }
        }

        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE project_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$projectId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }
}
