/**
 * Shared Kanban Board Module
 * Provides reusable Kanban board functionality for admin, manager, and employee views
 */

const statusColors = {
    'todo': 'secondary',
    'in_progress': 'primary',
    'done': 'success',
    'blocked': 'danger'
};

const priorityLabels = {
    1: 'low',
    2: 'medium',
    3: 'high'
};

const statusLabels = {
    'todo': 'To Do',
    'in_progress': 'In Progress',
    'done': 'Done',
    'blocked': 'Blocked'
};

/**
 * Create Kanban card HTML
 */
function createTaskCard(task) {
    const priorityClass = `priority-${priorityLabels[task.task_priority_id] || 'medium'}`;
    const assignee = task.assigned_first_name ? `${task.assigned_first_name} ${task.assigned_last_name || ''}`.trim() : 'Unassigned';

    let dueDateHtml = '';
    if (task.due_date) {
        const dueDate = new Date(task.due_date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const isOverdue = dueDate < today && task.status_name !== 'done';
        dueDateHtml = `<span class="kanban-card-due ${isOverdue ? 'overdue' : ''}">
            <i class="bi bi-calendar"></i> ${dueDate.toLocaleDateString()}
        </span>`;
    }

    return `
        <div class="kanban-card ${priorityClass}" 
             data-task-id="${task.id}" 
             data-status="${task.status_name}"
             draggable="true">
            <div class="kanban-card-title">${escapeHtml(task.name)}</div>
            <div class="kanban-card-meta">
                <span class="badge bg-${statusColors[priorityLabels[task.task_priority_id]] || 'secondary'}">
                    ${priorityLabels[task.task_priority_id] || 'medium'}
                </span>
                <span>${assignee}</span>
                ${dueDateHtml}
                ${task.comment_count > 0 ? `<span><i class="bi bi-chat"></i> ${task.comment_count}</span>` : ''}
            </div>
        </div>
    `;
}

/**
 * Render Kanban board
 */
function renderKanbanBoard(containerSelector, tasks, canDragDrop) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const statuses = ['todo', 'in_progress', 'done', 'blocked'];

    let html = '';
    statuses.forEach(status => {
        const statusTasks = tasks[status] || [];
        html += `
            <div class="kanban-column ${status}" data-status="${status}">
                <div class="kanban-column-header">
                    <span>${statusLabels[status]}</span>
                    <span class="badge bg-secondary">${statusTasks.length}</span>
                </div>
                <div class="kanban-cards" data-status="${status}">
                    ${statusTasks.map(task => createTaskCard(task)).join('')}
                    ${statusTasks.length === 0 ? '<div class="empty-state"><small>No tasks</small></div>' : ''}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    container.classList.toggle('drag-disabled', !canDragDrop);
}

/**
 * Initialize drag-and-drop functionality
 */
function initDragDrop(containerSelector, onStatusChange) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    container.addEventListener('dragstart', (e) => {
        if (!e.target.classList.contains('kanban-card')) return;
        if (container.classList.contains('drag-disabled')) {
            e.preventDefault();
            return;
        }
        e.target.classList.add('dragging');
        e.dataTransfer.setData('text/plain', e.target.dataset.taskId);
    });

    container.addEventListener('dragend', (e) => {
        if (!e.target.classList.contains('kanban-card')) return;
        e.target.classList.remove('dragging');
    });

    container.addEventListener('dragover', (e) => {
        if (container.classList.contains('drag-disabled')) return;
        const cards = e.target.closest('.kanban-cards');
        if (!cards) return;
        e.preventDefault();
    });

    container.addEventListener('drop', async (e) => {
        if (container.classList.contains('drag-disabled')) return;
        const cards = e.target.closest('.kanban-cards');
        if (!cards) return;
        e.preventDefault();

        const taskId = e.dataTransfer.getData('text/plain');
        const newStatus = cards.dataset.status;
        const card = container.querySelector(`.kanban-card[data-task-id="${taskId}"]`);

        if (!card || card.dataset.status === newStatus) return;

        // Move card visually
        cards.appendChild(card);
        card.dataset.status = newStatus;

        // Call status change handler
        if (onStatusChange) {
            await onStatusChange(taskId, newStatus);
        }
    });
}

/**
 * Initialize card click for task detail modal
 */
function initCardClick(containerSelector, onCardClick) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    container.addEventListener('click', (e) => {
        const card = e.target.closest('.kanban-card');
        if (!card) return;
        if (onCardClick) {
            onCardClick(card.dataset.taskId);
        }
    });
}

/**
 * Get status ID from status name
 */
function getStatusId(statusName) {
    const statusMap = {
        'todo': 1,
        'in_progress': 2,
        'done': 3,
        'blocked': 4
    };
    return statusMap[statusName] || 1;
}

/**
 * Format activity log entry
 */
function formatActivityLog(log) {
    const userName = log.first_name ? `${log.first_name} ${log.last_name || ''}`.trim() : 'System';
    const time = new Date(log.created_at).toLocaleString();

    let actionText = log.action.replace(/_/g, ' ');

    // Add details if available
    if (log.details) {
        if (log.action === 'status_changed' && log.details.old_status_id && log.details.new_status_id) {
            const oldStatus = getStatusName(log.details.old_status_id);
            const newStatus = getStatusName(log.details.new_status_id);
            actionText = `changed status from "${oldStatus}" to "${newStatus}"`;
        }
    }

    return `
        <div class="activity-item">
            <span class="activity-user">${escapeHtml(userName)}</span>
            <span class="activity-action">${actionText}</span>
            <div class="activity-time">${time}</div>
        </div>
    `;
}

/**
 * Get status name from ID
 */
function getStatusName(statusId) {
    const statusMap = {
        1: 'To Do',
        2: 'In Progress',
        3: 'Done',
        4: 'Blocked'
    };
    return statusMap[statusId] || 'Unknown';
}

/**
 * Format chat message
 */
function formatChatMessage(comment) {
    const author = `${comment.first_name} ${comment.last_name || ''}`.trim();
    const time = new Date(comment.created_at).toLocaleString();

    return `
        <div class="chat-message">
            <div class="chat-message-header">
                <span class="chat-message-author">${escapeHtml(author)}</span>
                <span>${time}</span>
            </div>
            <div class="chat-message-content">${escapeHtml(comment.comment)}</div>
        </div>
    `;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show notification using SweetAlert
 */
function showNotification(type, title, message = '') {
    Swal.fire({
        icon: type,
        title: title,
        text: message,
        timer: type === 'success' ? 1500 : undefined,
        showConfirmButton: type !== 'success'
    });
}

// Export functions
export {
    statusColors,
    priorityLabels,
    statusLabels,
    createTaskCard,
    renderKanbanBoard,
    initDragDrop,
    initCardClick,
    getStatusId,
    getStatusName,
    formatActivityLog,
    formatChatMessage,
    escapeHtml,
    showNotification
};
