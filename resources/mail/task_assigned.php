<p>Hi <?= htmlspecialchars($assigneeName) ?>,</p>

<p>A new task has been assigned to you by <strong><?= htmlspecialchars($assignerName) ?></strong>.</p>

<p><strong>Task Details:</strong></p>
<ul>
    <li>Task: <?= htmlspecialchars($taskName) ?></li>
    <li>Project: <?= htmlspecialchars($projectName) ?></li>
    <li>Priority: <?= htmlspecialchars(ucfirst($priority)) ?></li>
    <?php if (!empty($dueDate)): ?>
        <li>Due Date: <?= htmlspecialchars($dueDate) ?></li>
    <?php endif; ?>
</ul>

<?php if (!empty($description)): ?>
    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($description)) ?></p>
<?php endif; ?>

<p>Log in to the Task Management System to view and update your task.</p>

<p>Thanks,<br>Task Management System</p>