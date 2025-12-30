/**
 * Employee Tasks - Kanban Board Implementation
 * View only + drag-drop for assigned tasks
 */
import {
    renderKanbanBoard,
    initDragDrop,
    initCardClick,
    getStatusId,
    formatActivityLog,
    formatChatMessage,
    showNotification,
    escapeHtml
} from '/assets/js/kanban.js';

// State
let currentProjectId = null;
let currentTaskId = null;
let tasksData = {}; // Local state for tasks { todo: [], in_progress: [], done: [], blocked: [] }
let canDragDrop = false;
let chatRefreshInterval = null; // Interval for auto-refreshing chat

// DOM Elements
const projectSelect = document.getElementById('projectSelect');
const kanbanContainer = document.getElementById('kanbanContainer');
const emptyState = document.getElementById('emptyState');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initProjectSelector();
    initDragDrop('#kanbanContainer', handleStatusChange);
    initCardClick('#kanbanContainer', openTaskModal);
    initChatForm();
    initActivityTabRefresh();
});

function initProjectSelector() {
    projectSelect.addEventListener('change', () => {
        currentProjectId = projectSelect.value ? parseInt(projectSelect.value) : null;
        if (currentProjectId) {
            loadKanbanBoard();
            emptyState.classList.add('d-none');
            kanbanContainer.classList.remove('d-none');
        } else {
            emptyState.classList.remove('d-none');
            kanbanContainer.classList.add('d-none');
        }
    });
}

async function loadKanbanBoard() {
    if (!currentProjectId) return;
    kanbanContainer.innerHTML = '<div class="text-center p-5">Loading...</div>';

    try {
        const res = await fetch(`/api/employee/tasks/kanban?project_id=${currentProjectId}`);
        const result = await res.json();

        if (!result.success) {
            showNotification('error', 'Error', result.message);
            return;
        }

        // Store in local state
        tasksData = result.data.tasks;
        canDragDrop = result.data.can_drag_drop;
        renderKanbanBoard('#kanbanContainer', tasksData, canDragDrop);
    } catch (err) {
        showNotification('error', 'Network Error');
    }
}

/**
 * Update task status in local state and re-render
 */
function updateLocalTaskStatus(taskId, newStatus) {
    const statuses = ['todo', 'in_progress', 'done', 'blocked'];
    let movedTask = null;

    // Find and remove task from current status
    for (const status of statuses) {
        if (!tasksData[status]) continue;
        const index = tasksData[status].findIndex(t => t.id == taskId);
        if (index !== -1) {
            movedTask = tasksData[status].splice(index, 1)[0];
            break;
        }
    }

    // Add to new status column
    if (movedTask) {
        movedTask.status_name = newStatus;
        if (!tasksData[newStatus]) tasksData[newStatus] = [];
        tasksData[newStatus].push(movedTask);
    }

    // Re-render from local state (no loading flash)
    renderKanbanBoard('#kanbanContainer', tasksData, canDragDrop);
}

async function handleStatusChange(taskId, statusName) {
    const statusId = getStatusId(statusName);

    try {
        const res = await fetch('/api/employee/task/status', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: parseInt(taskId), status_id: statusId })
        });
        const result = await res.json();

        if (!result.success) {
            showNotification('error', 'Error', result.message);
            loadKanbanBoard(); // Reload to revert
        } else {
            // Update local state and re-render (no loading flash)
            updateLocalTaskStatus(taskId, statusName);
        }
    } catch (err) {
        showNotification('error', 'Network Error');
        loadKanbanBoard(); // Reload to revert
    }
}

async function openTaskModal(taskId) {
    currentTaskId = parseInt(taskId);

    try {
        const res = await fetch(`/api/employee/task/details?task_id=${taskId}`);
        const result = await res.json();

        if (!result.success) {
            showNotification('error', 'Error', result.message);
            return;
        }

        const task = result.data.task;

        document.getElementById('taskDetailTitle').textContent = task.name;
        document.getElementById('taskDetailDescription').innerHTML = task.description ? escapeHtml(task.description) : '<em>No description</em>';
        document.getElementById('taskDetailProject').textContent = task.project_name;
        document.getElementById('taskDetailStatus').innerHTML = `<span class="badge bg-secondary">${task.status_name.replace('_', ' ')}</span>`;
        document.getElementById('taskDetailPriority').innerHTML = `<span class="badge bg-secondary">${task.priority_name}</span>`;
        document.getElementById('taskDetailDueDate').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'Not set';
        document.getElementById('taskDetailAssignee').textContent = task.assigned_first_name ?
            `${task.assigned_first_name} ${task.assigned_last_name || ''}` : 'Unassigned';
        document.getElementById('taskDetailCreatedAt').textContent = new Date(task.created_at).toLocaleString();

        loadTaskComments();
        loadTaskActivity();

        // Start chat auto-refresh every 2 seconds
        startChatAutoRefresh();

        const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
        modal.show();

        // Stop auto-refresh when modal is closed
        document.getElementById('taskDetailModal').addEventListener('hidden.bs.modal', () => {
            stopChatAutoRefresh();
            currentTaskId = null;
        }, { once: true });
    } catch (err) {
        showNotification('error', 'Network Error');
    }
}

/**
 * Start chat auto-refresh interval
 */
function startChatAutoRefresh() {
    stopChatAutoRefresh(); // Clear any existing interval
    chatRefreshInterval = setInterval(() => {
        if (currentTaskId) {
            loadTaskComments();
        }
    }, 2000);
}

/**
 * Stop chat auto-refresh interval
 */
function stopChatAutoRefresh() {
    if (chatRefreshInterval) {
        clearInterval(chatRefreshInterval);
        chatRefreshInterval = null;
    }
}

async function loadTaskComments() {
    if (!currentTaskId) return;
    const container = document.getElementById('taskChatContainer');
    container.innerHTML = '<div class="text-center p-2">Loading...</div>';

    try {
        const res = await fetch(`/api/employee/task/comments?task_id=${currentTaskId}`);
        const result = await res.json();

        if (result.success) {
            const comments = result.data.comments;
            container.innerHTML = comments.length === 0
                ? '<div class="text-center text-muted p-2">No messages yet</div>'
                : comments.map(c => formatChatMessage(c)).join('');
            container.scrollTop = container.scrollHeight;
        }
    } catch (err) {
        container.innerHTML = '<div class="text-center text-danger p-2">Error</div>';
    }
}

async function loadTaskActivity() {
    if (!currentTaskId) return;
    const container = document.getElementById('taskActivityContainer');
    container.innerHTML = '<div class="text-center p-2">Loading...</div>';

    try {
        const res = await fetch(`/api/employee/task/activity-logs?task_id=${currentTaskId}`);
        const result = await res.json();

        if (result.success) {
            const logs = result.data.data;
            container.innerHTML = logs.length === 0
                ? '<div class="text-center text-muted p-2">No activity</div>'
                : logs.map(l => formatActivityLog(l)).join('');
        }
    } catch (err) {
        container.innerHTML = '<div class="text-center text-danger p-2">Error</div>';
    }
}

function initChatForm() {
    document.getElementById('taskChatForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('taskChatInput');
        const comment = input.value.trim();
        if (!comment || !currentTaskId) return;

        const formData = new FormData();
        formData.append('task_id', currentTaskId);
        formData.append('comment', comment);

        try {
            const res = await fetch('/api/employee/task/comment', { method: 'POST', body: formData });
            const result = await res.json();
            if (result.success) {
                input.value = '';
                loadTaskComments();
            } else {
                showNotification('error', 'Error', result.message);
            }
        } catch (err) {
            showNotification('error', 'Network Error');
        }
    });
}

/**
 * Initialize activity tab to refresh on click
 */
function initActivityTabRefresh() {
    // Find the Activity tab button and add click handler
    const activityTab = document.querySelector('[data-bs-target="#activityTab"]');
    activityTab?.addEventListener('shown.bs.tab', () => {
        if (currentTaskId) {
            loadTaskActivity();
        }
    });
}
