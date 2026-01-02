<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>Admin Dashboard</h1>
        </div>
        <div class="quick-links">
            <a href="/admin/users" class="btn btn-outline-primary btn-sm">Manage Users</a>
            <a href="/admin/projects" class="btn btn-outline-primary btn-sm">All Projects</a>
            <a href="/admin/tasks" class="btn btn-outline-primary btn-sm">All Tasks</a>
            <a href="/admin/team-assignments" class="btn btn-outline-primary btn-sm">Team Assignments</a>
        </div>
    </div>

    <!-- User Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">User Stats</h4>
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="bi bi-people-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $userStats['total_users'] ?? 0 ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-person-check-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $userStats['active_users'] ?? 0 ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-person-badge-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $userStats['total_managers'] ?? 0 ?></div>
                    <div class="stat-label">Managers</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-person-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $userStats['total_employees'] ?? 0 ?></div>
                    <div class="stat-label">Employees</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">Project Stats</h4>
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="bi bi-folder-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $projectStats['total_projects'] ?? 0 ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-folder-check stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $projectStats['completed_projects'] ?? 0 ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-arrow-repeat stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $projectStats['in_progress_projects'] ?? 0 ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">Task Stats</h4>
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="bi bi-clipboard2-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['total_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-clipboard2-check-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['completed_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-exclamation-triangle-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['overdue_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <h3>Recent Activities</h3>
        <?php if (empty($recentActivities)): ?>
            <p class="text-muted">No recent activities.</p>
        <?php else: ?>
            <div class="activity-log activity-log-tall">
                <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <span class="activity-user"><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></span>
                        <span class="activity-action"><?= str_replace('_', ' ', $activity['action']) ?></span>
                        <?php if ($activity['task_name']): ?>
                            <span>on "<?= htmlspecialchars($activity['task_name']) ?>"</span>
                        <?php endif; ?>
                        <?php if ($activity['project_name']): ?>
                            <span>in <?= htmlspecialchars($activity['project_name']) ?></span>
                        <?php endif; ?>
                        <span class="activity-time"><?= date('M d, H:i', strtotime($activity['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>