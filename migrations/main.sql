CREATE DATABASE IF NOT EXISTS task_management_system;
USE task_management_system;

CREATE TABLE roles (
    id TINYINT UNSIGNED PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO roles (id, name) VALUES
(1, 'super_admin'),
(2, 'manager'),
(3, 'employee');

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(128) NOT NULL,
    last_name VARCHAR(128),
    email VARCHAR(128) NOT NULL,
    password VARCHAR(128) NOT NULL,
    phone_number VARCHAR(16) NOT NULL,
    role_id TINYINT UNSIGNED NOT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT uq_users_phone_number UNIQUE (phone_number),

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_users_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
);

INSERT INTO users 
    (first_name, last_name, email, password, phone_number, role_id, created_by, is_active)
VALUES 
    ('Super', 'Admin', 'admin@gmail.com', '$2y$10$fmWMLa3gbOAAgd6fQRxt6u50pRcZWfqdTrJZW/kcuGbWv3vYt3Phu', '9999999999', 1, NULL, 1);

CREATE TABLE manager_team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    manager_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_mtm UNIQUE (manager_id, member_id),

    CONSTRAINT fk_mtm_manager
        FOREIGN KEY (manager_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_mtm_member
        FOREIGN KEY (member_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE project_statuses (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO project_statuses (id, name) VALUES
(1, 'not_started'),
(2, 'in_progress'),
(3, 'on_hold'),
(4, 'completed');

CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by INT UNSIGNED NOT NULL,
    name VARCHAR(128) NOT NULL,
    description TEXT DEFAULT NULL,
    project_status_id TINYINT UNSIGNED NOT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_projects_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_projects_status
        FOREIGN KEY (project_status_id) REFERENCES project_statuses(id)
        ON DELETE RESTRICT
);

CREATE TABLE project_user_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_project_user UNIQUE (project_id, user_id),

    CONSTRAINT fk_pua_project
        FOREIGN KEY (project_id) REFERENCES projects(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pua_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE task_statuses (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO task_statuses (id, name) VALUES
(1, 'todo'),
(2, 'in_progress'),
(3, 'done'),
(4, 'blocked');

CREATE TABLE task_priorities (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO task_priorities (id, name) VALUES
(1, 'low'),
(2, 'medium'),
(3, 'high');

CREATE TABLE project_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    task_status_id TINYINT UNSIGNED NOT NULL,
    task_priority_id TINYINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tasks_project
        FOREIGN KEY (project_id) REFERENCES projects(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_tasks_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_tasks_status
        FOREIGN KEY (task_status_id) REFERENCES task_statuses(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_tasks_priority
        FOREIGN KEY (task_priority_id) REFERENCES task_priorities(id)
        ON DELETE RESTRICT
);

CREATE TABLE project_task_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ptc_task
        FOREIGN KEY (task_id) REFERENCES project_tasks(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ptc_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON DELETE RESTRICT
);

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

-- 1
ALTER TABLE project_task_comments
ADD COLUMN project_id INT UNSIGNED NULL AFTER id;

-- 2
UPDATE project_task_comments ptc
JOIN project_tasks pt ON ptc.task_id = pt.id
SET ptc.project_id = pt.project_id;

-- 3
ALTER TABLE project_task_comments
MODIFY project_id INT UNSIGNED NOT NULL,
ADD CONSTRAINT fk_ptc_project
FOREIGN KEY (project_id)
REFERENCES projects(id)
ON DELETE CASCADE;

ALTER TABLE projects
  ADD COLUMN manager_id INT UNSIGNED NOT NULL AFTER created_by,
  ADD KEY fk_projects_manager (manager_id),
  ADD CONSTRAINT fk_projects_manager
    FOREIGN KEY (manager_id)
    REFERENCES users(id)
    ON DELETE RESTRICT;

UPDATE `project_statuses` SET `name` = 'pending' WHERE `project_statuses`.`id` = 1;