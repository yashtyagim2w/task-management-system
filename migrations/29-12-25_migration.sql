-- Add assigned_to column to project_tasks table
ALTER TABLE `project_tasks`
ADD COLUMN `assigned_to` INT UNSIGNED NULL AFTER `created_by`,
ADD CONSTRAINT `fk_project_tasks_assigned_to` 
    FOREIGN KEY (`assigned_to`) 
    REFERENCES `users`(`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- Add index for better query performance
CREATE INDEX `idx_project_tasks_assigned_to` ON `project_tasks`(`assigned_to`);

CREATE TABLE `project_task_activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

    `project_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED NULL,

    `user_id` INT UNSIGNED NULL,

    `action` VARCHAR(64) NOT NULL COMMENT 
        'created, added, updated, status_changed, priority_changed, assigned, unassigned, deleted, etc.',

    `details` JSON NULL COMMENT 
        'Old and new values, additional context',

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    CONSTRAINT `fk_ptal_project`
        FOREIGN KEY (`project_id`)
        REFERENCES `projects`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT `fk_ptal_task`
        FOREIGN KEY (`task_id`)
        REFERENCES `project_tasks`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT `fk_ptal_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX `idx_ptal_project_id` (`project_id`),
    INDEX `idx_ptal_task_id` (`task_id`),
    INDEX `idx_ptal_user_id` (`user_id`),
    INDEX `idx_ptal_action` (`action`),
    INDEX `idx_ptal_created_at` (`created_at`)
);
