<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>Manager Dashboard</h1>
        </div>
        <div class="quick-links">
            <a href="/manager/team" class="btn btn-outline-primary btn-sm">My Team</a>
            <a href="/manager/projects" class="btn btn-outline-primary btn-sm">My Projects</a>
            <a href="/manager/tasks" class="btn btn-outline-primary btn-sm">Tasks</a>
        </div>
    </div>

    <!-- Team Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">Team Stats</h4>
        <div class="dashboard-stats">
            <a href="/manager/team" class="stat-card stat-card-link">
                <i class="bi bi-people-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $teamSize ?? 0 ?></div>
                    <div class="stat-label">Team Members</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Project Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">Project Stats</h4>
        <div class="dashboard-stats">
            <a href="/manager/projects" class="stat-card stat-card-link">
                <i class="bi bi-folder-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $projectStats['total_projects'] ?? 0 ?></div>
                    <div class="stat-label">My Projects</div>
                </div>
            </a>
            <a href="/manager/projects?status_id=4" class="stat-card stat-card-link">
                <i class="bi bi-folder-check stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $projectStats['completed_projects'] ?? 0 ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </a>
            <a href="/manager/projects?status_id=2" class="stat-card stat-card-link">
                <i class="bi bi-arrow-repeat stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $projectStats['in_progress_projects'] ?? 0 ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Task Stats -->
    <div class="stats-category">
        <h4 class="stats-category-title">Task Stats</h4>
        <div class="dashboard-stats">
            <a href="/manager/tasks" class="stat-card stat-card-link">
                <i class="bi bi-clipboard2-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['total_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </a>
            <a href="/manager/tasks" class="stat-card stat-card-link">
                <i class="bi bi-clipboard2-check-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['completed_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </a>
            <a href="/manager/tasks" class="stat-card stat-card-link">
                <i class="bi bi-exclamation-triangle-fill stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-value"><?= $taskStats['overdue_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </a>
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