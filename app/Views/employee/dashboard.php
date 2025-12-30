<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>Employee Dashboard</h1>
        </div>
        <div class="page-nav">
            <a href="/employee/projects" class="btn btn-primary">My Projects</a>
            <a href="/employee/tasks" class="btn btn-primary">My Tasks</a>
        </div>
    </div>

    <div class="dashboard-section">
        <h3>Tasks Due Soon</h3>
        <?php if (empty($dueSoonTasks)): ?>
            <p class="text-muted">No tasks due in the next 3 days.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Project</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dueSoonTasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['name']) ?></td>
                                <td><?= htmlspecialchars($task['project_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $task['priority_name'] === 'high' ? 'danger' : ($task['priority_name'] === 'medium' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($task['priority_name']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($task['due_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>