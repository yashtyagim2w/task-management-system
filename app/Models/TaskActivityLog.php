<?php

namespace App\Models;

use App\Core\Model;

class TaskActivityLog extends Model
{
    protected string $tableName = "project_task_activity_logs";

    /**
     * Log a task activity
     */
    public function logActivity(int $projectId, ?int $taskId, ?int $userId, string $action, ?array $details = null): bool
    {
        $detailsJson = $details ? json_encode($details) : null;

        $sql = "INSERT INTO {$this->tableName} (project_id, task_id, user_id, action, details) 
                VALUES (?, ?, ?, ?, ?)";

        return $this->rawExecute($sql, "iiiss", [$projectId, $taskId, $userId, $action, $detailsJson]);
    }

    /**
     * Get activity logs for a task
     */
    public function getTaskLogs(int $taskId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
            tal.id,
            tal.project_id,
            tal.task_id,
            tal.user_id,
            tal.action,
            tal.details,
            tal.created_at,
            u.first_name,
            u.last_name,
            u.email
        FROM {$this->tableName} tal
        LEFT JOIN users u ON tal.user_id = u.id
        WHERE tal.task_id = ?
        ORDER BY tal.created_at DESC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$taskId, $limit, $offset]);

        // Parse JSON details
        foreach ($data as &$log) {
            if ($log['details']) {
                $log['details'] = json_decode($log['details'], true);
            }
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE task_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$taskId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get activity logs for a project (all tasks)
     */
    public function getProjectLogs(int $projectId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
            tal.id,
            tal.project_id,
            tal.task_id,
            tal.user_id,
            tal.action,
            tal.details,
            tal.created_at,
            u.first_name,
            u.last_name,
            u.email,
            t.name as task_name
        FROM {$this->tableName} tal
        LEFT JOIN users u ON tal.user_id = u.id
        LEFT JOIN project_tasks t ON tal.task_id = t.id
        WHERE tal.project_id = ?
        ORDER BY tal.created_at DESC
        LIMIT ? OFFSET ?";

        $data = $this->rawQuery($sql, "iii", [$projectId, $limit, $offset]);

        // Parse JSON details
        foreach ($data as &$log) {
            if ($log['details']) {
                $log['details'] = json_decode($log['details'], true);
            }
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE project_id = ?";
        $countResult = $this->rawQuery($countSql, "i", [$projectId]);
        $totalCount = (int)($countResult[0]['total'] ?? 0);

        return [
            'data' => $data,
            'total_count' => $totalCount
        ];
    }

    /**
     * Get recent activities for dashboard
     * @param int|null $managerId Filter by manager's projects only
     */
    public function getRecentActivities(?int $managerId = null, int $limit = 10): array
    {
        $condition = $managerId !== null ? "AND p.manager_id = ?" : "";
        $params = $managerId !== null ? [$managerId, $limit] : [$limit];
        $types = $managerId !== null ? "ii" : "i";

        $sql = "SELECT 
            tal.id,
            tal.action,
            tal.created_at,
            u.first_name,
            u.last_name,
            t.name as task_name,
            p.name as project_name
        FROM {$this->tableName} tal
        JOIN projects p ON tal.project_id = p.id
        LEFT JOIN users u ON tal.user_id = u.id
        LEFT JOIN project_tasks t ON tal.task_id = t.id
        WHERE p.is_deleted = 0 {$condition}
        ORDER BY tal.created_at DESC
        LIMIT ?";

        return $this->rawQuery($sql, $types, $params);
    }
}
