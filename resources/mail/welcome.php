<p>Hi <?= htmlspecialchars($userName) ?>,</p>

<p>Welcome to the Task Management System! Your account has been successfully created.</p>

<p><strong>Your Login Credentials:</strong></p>
<ul>
    <li>Email: <?= htmlspecialchars($userEmail) ?></li>
    <li>Password: <?= htmlspecialchars($userPassword) ?></li>
    <li>Role: <?= htmlspecialchars($userRole) ?></li>
</ul>

<p>If you have any questions, please contact your administrator.</p>

<p>Thanks,<br>Task Management System</p>