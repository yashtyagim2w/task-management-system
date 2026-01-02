<p>Hi <?= htmlspecialchars($employeeName) ?>,</p>

<p>You have been assigned to a new project!</p>

<p><strong>Project Details:</strong></p>
<ul>
    <li>Project: <?= htmlspecialchars($projectName) ?></li>
    <li>Manager: <?= htmlspecialchars($managerName) ?></li>
    <?php if (!empty($projectDescription)): ?>
        <li>Description: <?= htmlspecialchars($projectDescription) ?></li>
    <?php endif; ?>
</ul>

<p>Log in to the Task Management System to view the project and your assigned tasks.</p>

<p>Thanks,<br>Task Management System</p>