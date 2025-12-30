<main>
    <div class="main-nav">
        <h4 class="nav-heading">Tasks - Kanban Board</h4>
        <div class="page-nav">
            <button type="button" class="btn btn-primary" id="createTaskBtn">
                <i class="bi bi-plus-circle"></i> Create Task
            </button>
            <button type="button" class="btn btn-outline-secondary" id="showProjectActivityBtn">
                <i class="bi bi-activity"></i> Project Activity
            </button>
        </div>
    </div>

    <!-- Project Selector -->
    <div class="project-selector">
        <label class="form-label mb-0 fw-bold">Select Project:</label>
        <select id="projectSelect" class="form-select">
            <option value="">-- Select a Project --</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="empty-state">
        <i class="bi bi-kanban"></i>
        <h5>Select a Project</h5>
        <p>Choose a project from the dropdown above to view its task board</p>
    </div>

    <!-- Kanban Board Container -->
    <div id="kanbanContainer" class="kanban-container d-none"></div>
</main>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createTaskForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Task Name *</label>
                        <input type="text" name="name" class="form-control" minlength="3" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status_id" class="form-select">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= $status['id'] ?>"><?= ucfirst(str_replace('_', ' ', $status['name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority_id" class="form-select">
                                <?php foreach ($priorities as $priority): ?>
                                    <option value="<?= $priority['id'] ?>" <?= $priority['id'] == 2 ? 'selected' : '' ?>>
                                        <?= ucfirst($priority['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" id="create_assigned_to" class="form-select">
                                <option value="">Unassigned</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Task Detail Modal (Right Side) -->
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
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editTab">Edit</button>
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
                    </div>

                    <div class="tab-pane fade" id="editTab">
                        <form id="editTaskForm" class="p-3">
                            <input type="hidden" id="edit_task_id">
                            <div class="mb-3">
                                <label class="form-label">Task Name *</label>
                                <input type="text" id="edit_task_name" class="form-control" minlength="3" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea id="edit_task_description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select id="edit_task_status_id" class="form-select">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status['id'] ?>"><?= ucfirst(str_replace('_', ' ', $status['name'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Priority</label>
                                    <select id="edit_task_priority_id" class="form-select">
                                        <?php foreach ($priorities as $priority): ?>
                                            <option value="<?= $priority['id'] ?>"><?= ucfirst($priority['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" id="edit_task_due_date" class="form-control" min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Assign To</label>
                                    <select id="edit_assigned_to" class="form-select">
                                        <option value="">Unassigned</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">Save Changes</button>
                                <button type="button" id="deleteTaskBtn" class="btn btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="chatTab">
                        <div id="taskChatContainer" class="chat-container"></div>
                        <form id="taskChatForm" class="chat-input-container">
                            <input type="text" id="taskChatInput" class="form-control" placeholder="Type a message..." required>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="activityTab">
                        <div id="taskActivityContainer" class="activity-log p-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Project Activity Modal -->
<div class="modal fade" id="projectActivityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Project Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="projectActivityContainer" class="activity-log" style="max-height: 500px;"></div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/assets/js/manager/tasks.js"></script>