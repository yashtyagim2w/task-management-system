<main>
    <div class="main-nav">
        <h4 class="nav-heading">My Tasks - Kanban Board</h4>
    </div>

    <div class="filters-container">
        <div class="filters-selection">
            <select id="projectSelect">
                <option value="">-- Select Project --</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="priorityFilter">
                <option value="">All Priorities</option>
                <?php foreach ($priorities as $priority): ?>
                    <option value="<?= $priority['id'] ?>"><?= ucfirst($priority['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="button" id="resetFiltersBtn" class="btn btn-danger">Reset</button>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="empty-state">
        <i class="bi bi-kanban"></i>
        <h5>Select a Project</h5>
        <p>Choose a project from the dropdown above to view your assigned tasks</p>
    </div>

    <!-- Kanban Board Container -->
    <div id="kanbanContainer" class="kanban-container d-none"></div>
</main>

<!-- Task Detail Modal (Right Side) - View Only -->
<div class="modal fade task-detail-modal" id="taskDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailTitle">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#detailsTab">Details</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#chatTab">Chat</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#activityTab">Activity</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="detailsTab">
                        <div class="task-detail-section">
                            <div class="task-detail-label">Description</div>
                            <div class="task-detail-value" id="taskDetailDescription"></div>
                        </div>
                        <div class="task-detail-section">
                            <div class="row">
                                <div class="col-6">
                                    <div class="task-detail-label">Project</div>
                                    <div class="task-detail-value" id="taskDetailProject"></div>
                                </div>
                                <div class="col-6">
                                    <div class="task-detail-label">Assignee</div>
                                    <div class="task-detail-value" id="taskDetailAssignee"></div>
                                </div>
                            </div>
                        </div>
                        <div class="task-detail-section">
                            <div class="row">
                                <div class="col-6">
                                    <div class="task-detail-label">Status</div>
                                    <div class="task-detail-value" id="taskDetailStatus"></div>
                                </div>
                                <div class="col-6">
                                    <div class="task-detail-label">Priority</div>
                                    <div class="task-detail-value" id="taskDetailPriority"></div>
                                </div>
                            </div>
                        </div>
                        <div class="task-detail-section">
                            <div class="row">
                                <div class="col-6">
                                    <div class="task-detail-label">Due Date</div>
                                    <div class="task-detail-value" id="taskDetailDueDate"></div>
                                </div>
                                <div class="col-6">
                                    <div class="task-detail-label">Created</div>
                                    <div class="task-detail-value" id="taskDetailCreatedAt"></div>
                                </div>
                            </div>
                        </div>
                        <div class="task-detail-section">
                            <p class="text-muted small mb-0">
                                <i class="bi bi-info-circle"></i>
                                Drag and drop tasks between columns to update status
                            </p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="chatTab">
                        <div class="chat-tab">
                            <div id="taskChatContainer" class="chat-container"></div>
                            <form id="taskChatForm" class="chat-input-container">
                                <input type="text" id="taskChatInput" class="form-control" placeholder="Type a message..." minlength="1" maxlength="500" required>
                                <button type="submit" class="btn btn-primary">Send</button>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="activityTab">
                        <div id="taskActivityContainer" class="activity-log p-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/assets/js/employee/tasks.js"></script>