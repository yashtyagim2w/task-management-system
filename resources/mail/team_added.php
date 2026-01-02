<p>Hi <?= htmlspecialchars($employeeName) ?>,</p>

<p>You have been added to a team!</p>

<p><strong>Team Details:</strong></p>
<ul>
    <li>Manager: <?= htmlspecialchars($managerName) ?></li>
    <?php if (!empty($managerEmail)): ?>
        <li>Manager Email: <?= htmlspecialchars($managerEmail) ?></li>
    <?php endif; ?>
</ul>

<p>Your manager may assign you tasks and projects. Check your dashboard regularly for updates.</p>

<p>Log in to the Task Management System to get started!</p>

<p>Thanks,<br>Task Management System</p>