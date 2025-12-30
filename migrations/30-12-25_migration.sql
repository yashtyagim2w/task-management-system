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

UPDATE `project_statuses` SET `name` = 'pending' WHERE `project_statuses`.`id` = 1;