<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>Employee Dashboard</h1>
        </div>
        <div class="quick-links">
            <a href="/employee/projects" class="btn btn-outline-primary btn-sm">My Projects</a>
            <a href="/employee/tasks" class="btn btn-outline-primary btn-sm">My Tasks</a>
        </div>
    </div>

    <!-- Task Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">Task Stats</h4>
        <div class="dashboard-stats">
            <a href="/employee/tasks" class="stat-card stat-card-link">
                <i class="bi bi-clipboard2-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['total_tasks'] ?? 0 ?></div>
                    <div class="stat-label">My Tasks</div>
                </div>
            </a>
            <a href="/employee/tasks" class="stat-card stat-card-link">
                <i class="bi bi-clipboard2-check-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['completed_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </a>
            <a href="/employee/tasks" class="stat-card stat-card-link">
                <i class="bi bi-arrow-repeat stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['in_progress_tasks'] ?? 0 ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </a>
            <a href="/employee/tasks" class="stat-card stat-card-link">
                <i class="bi bi-exclamation-triangle-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['overdue_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </a>
            <a href="/employee/tasks" class="stat-card stat-card-link">
                <i class="bi bi-calendar-event-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['due_today'] ?? 0 ?></div>
                    <div class="stat-label">Due Today</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Project Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">Project Stats</h4>
        <div class="dashboard-stats">
            <a href="/employee/projects" class="stat-card stat-card-link">
                <i class="bi bi-folder-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $projectCount ?? 0 ?></div>
                    <div class="stat-label">My Projects</div>
                </div>
            </a>
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