<?php

namespace App\Models;

use App\Core\Model;

class TaskComments extends Model
{

    protected string $tableName = "project_task_comments";

    /**
     * Create a new comment
     */
    public function create(int $projectId, int $authorId, string $comment, ?int $taskId = null): int|false
    {
        if ($taskId === null) { 
            $sql = "INSERT INTO {$this->tableName} (project_id, author_id, comment) 
                    VALUES (?, ?, ?)";
            $success = $this->rawExecute($sql, "iis", [$projectId, $authorId, $comment]);
        } else {
            $sql = "INSERT INTO {$this->tableName} (project_id, task_id, author_id, comment) 
                    VALUES (?, ?, ?, ?)";
            $success = $this->rawExecute($sql, "iiis", [$projectId, $taskId, $authorId, $comment]);
        }

        if ($success) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Get comments for a task
     */
    public function getByTask(int $taskId): array
    {
        $sql = "SELECT 
            c.*,
            u.first_name,
            u.last_name,
            u.email
        FROM {$this->tableName} c
        JOIN users u ON c.author_id = u.id
        WHERE c.task_id = ?
        ORDER BY c.created_at";

        return $this->rawQuery($sql, "i", [$taskId]);
    }

    /**
     * Get comments for a project
     */
    public function getByProject(int $projectId): array
    {
        $sql = "SELECT 
            c.*,
            u.first_name,
            u.last_name,
            u.email
        FROM {$this->tableName} c
        JOIN users u ON c.author_id = u.id
        WHERE c.project_id = ?
        ORDER BY c.created_at";

        return $this->rawQuery($sql, "i", [$projectId]);
    }

    /**
     * Delete a comment
     */
    public function deleteComment(int $commentId): bool
    {
        $sql = "DELETE FROM {$this->tableName} WHERE id = ?";
        return $this->rawExecute($sql, "i", [$commentId]);
    }

    /**
     * Check if user is comment author
     */
    public function isAuthor(int $commentId, int $userId): bool
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE id = ? AND author_id = ? LIMIT 1";
        $result = $this->rawQuery($sql, "ii", [$commentId, $userId]);
        return count($result) > 0;
    }

    /**
     * Get comment count for a task
     */
    public function getCountByTask(int $taskId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE task_id = ?";
        $result = $this->rawQuery($sql, "i", [$taskId]);
        return (int)($result[0]['total'] ?? 0);
    }
}
