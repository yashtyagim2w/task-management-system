/**
 * Manager Tasks - Kanban Board Implementation
 * Similar to admin but restricted to manager's projects
 */
import {
    renderKanbanBoard,
    initDragDrop,
    initCardClick,
    getStatusId,
    showNotification,
    escapeHtml
} from '/assets/js/kanban.js';

import {
    initChatModule,
    setCurrentTaskId,
    startChatAutoRefresh,
    stopChatAutoRefresh,
    loadTaskComments,
    loadTaskActivity,
    initChatForm,
    initActivityTabRefresh
} from '/assets/js/chat.js';

// State
let currentProjectId = null;
let currentTaskId = null;
let tasksData = {}; // Local state for tasks
let canDragDrop = false;

// DOM Elements
const projectSelect = document.getElementById('projectSelect');
const kanbanContainer = document.getElementById('kanbanContainer');
const emptyState = document.getElementById('emptyState');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initChatModule('/api/manager');
    initProjectSelector();
    initDragDrop('#kanbanContainer', handleStatusChange);
    initCardClick('#kanbanContainer', openTaskModal);
    initTaskForms();
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
        const res = await fetch(`/api/manager/tasks/kanban?project_id=${currentProjectId}`);
        const result = await res.json();

        if (!result.success) {
            showNotification('error', 'Error', result.message);
            return;
        }

        // Store in local state
        tasksData = result.data.tasks;
        canDragDrop = result.data.can_drag_drop;
        renderKanbanBoard('#kanbanContainer', tasksData, canDragDrop);
        loadProjectAssignees();
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

    for (const status of statuses) {
        if (!tasksData[status]) continue;
        const index = tasksData[status].findIndex(t => t.id == taskId);
        if (index !== -1) {
            movedTask = tasksData[status].splice(index, 1)[0];
            break;
        }
    }

    if (movedTask) {
        movedTask.status_name = newStatus;
        if (!tasksData[newStatus]) tasksData[newStatus] = [];
        tasksData[newStatus].push(movedTask);
    }

    renderKanbanBoard('#kanbanContainer', tasksData, canDragDrop);
}

async function loadProjectAssignees() {
    if (!currentProjectId) return;

    try {
        const res = await fetch(`/api/manager/task/assignees?project_id=${currentProjectId}`);
        const result = await res.json();

        if (result.success) {
            const options = '<option value="">Unassigned</option>' +
                result.data.assignees.map(a =>
                    `<option value="${a.id}">${a.first_name} ${a.last_name || ''}</option>`
                ).join('');

            document.getElementById('create_assigned_to').innerHTML = options;
            document.getElementById('edit_assigned_to').innerHTML = options;
        }
    } catch (err) {
        console.error('Failed to load assignees');
    }
}

async function handleStatusChange(taskId, statusName) {
    const statusId = getStatusId(statusName);

    try {
        const res = await fetch('/api/manager/task/status', {
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
    setCurrentTaskId(currentTaskId);

    try {
        const res = await fetch(`/api/manager/task/details?task_id=${taskId}`);
        const result = await res.json();

        if (!result.success) {
            showNotification('error', 'Error', result.message);
            return;
        }

        const task = result.data.task;
        const assignees = result.data.assignees;

        document.getElementById('taskDetailTitle').textContent = task.name;
        document.getElementById('taskDetailDescription').innerHTML = task.description ? escapeHtml(task.description) : '<em>No description</em>';
        document.getElementById('taskDetailProject').textContent = task.project_name;
        document.getElementById('taskDetailStatus').innerHTML = `<span class="badge bg-secondary">${task.status_name.replace('_', ' ')}</span>`;
        document.getElementById('taskDetailPriority').innerHTML = `<span class="badge bg-secondary">${task.priority_name}</span>`;
        document.getElementById('taskDetailDueDate').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'Not set';
        document.getElementById('taskDetailAssignee').textContent = task.assigned_first_name ?
            `${task.assigned_first_name} ${task.assigned_last_name || ''}` : 'Unassigned';
        document.getElementById('taskDetailCreatedAt').textContent = new Date(task.created_at).toLocaleString();

        document.getElementById('edit_task_id').value = task.id;
        document.getElementById('edit_task_name').value = task.name;
        document.getElementById('edit_task_description').value = task.description || '';
        document.getElementById('edit_task_status_id').value = task.task_status_id;
        document.getElementById('edit_task_priority_id').value = task.task_priority_id;
        document.getElementById('edit_task_due_date').value = task.due_date || '';

        const editAssigneeSelect = document.getElementById('edit_assigned_to');
        editAssigneeSelect.innerHTML = '<option value="">Unassigned</option>' +
            assignees.map(a => `<option value="${a.id}" ${a.id == task.assigned_to ? 'selected' : ''}>${a.first_name} ${a.last_name || ''}</option>`).join('');

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
            setCurrentTaskId(null);
        }, { once: true });
    } catch (err) {
        showNotification('error', 'Network Error');
    }
}

function initTaskForms() {
    document.getElementById('createTaskForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.set('project_id', currentProjectId);

        const dueDate = formData.get('due_date');
        if (dueDate && new Date(dueDate) < new Date().setHours(0, 0, 0, 0)) {
            showNotification('error', 'Invalid Date', 'Due date cannot be in the past');
            return;
        }

        // Show loading overlay
        Swal.fire({
            title: 'Creating Task...',
            text: 'Please wait while we create the task and send notifications',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch('/manager/task/create', { method: 'POST', body: formData });
            const result = await res.json();

            if (result.success) {
                showNotification('success', 'Task Created');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('createTaskModal')).hide();
                loadKanbanBoard();
            } else {
                showNotification('error', 'Error', result.message);
            }
        } catch (err) {
            showNotification('error', 'Network Error');
        }
    });

    document.getElementById('editTaskForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const dueDate = document.getElementById('edit_task_due_date').value;
        if (dueDate && new Date(dueDate) < new Date().setHours(0, 0, 0, 0)) {
            showNotification('error', 'Invalid Date', 'Due date cannot be in the past');
            return;
        }

        const payload = {
            task_id: parseInt(document.getElementById('edit_task_id').value),
            name: document.getElementById('edit_task_name').value.trim(),
            description: document.getElementById('edit_task_description').value.trim(),
            status_id: parseInt(document.getElementById('edit_task_status_id').value),
            priority_id: parseInt(document.getElementById('edit_task_priority_id').value),
            due_date: dueDate || null,
            assigned_to: document.getElementById('edit_assigned_to').value || null
        };

        // Show loading overlay
        Swal.fire({
            title: 'Updating Task...',
            text: 'Please wait while we save changes and send notifications',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch('/api/manager/task', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                showNotification('success', 'Task Updated');
                bootstrap.Modal.getInstance(document.getElementById('taskDetailModal')).hide();
                loadKanbanBoard();
            } else {
                showNotification('error', 'Error', result.message);
            }
        } catch (err) {
            showNotification('error', 'Network Error');
        }
    });

    document.getElementById('deleteTaskBtn')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: 'Delete Task?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Delete'
        });

        if (!confirmed.isConfirmed) return;

        try {
            const res = await fetch('/api/manager/task', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: currentTaskId })
            });
            const result = await res.json();

            if (result.success) {
                showNotification('success', 'Task Deleted');
                bootstrap.Modal.getInstance(document.getElementById('taskDetailModal')).hide();
                loadKanbanBoard();
            } else {
                showNotification('error', 'Error', result.message);
            }
        } catch (err) {
            showNotification('error', 'Network Error');
        }
    });
}

document.getElementById('showProjectActivityBtn')?.addEventListener('click', async () => {
    if (!currentProjectId) return;
    const container = document.getElementById('projectActivityContainer');
    container.innerHTML = '<div class="text-center p-2">Loading...</div>';

    try {
        const res = await fetch(`/api/manager/project/activity-logs?project_id=${currentProjectId}`);
        const result = await res.json();

        if (result.success) {
            const logs = result.data.data;
            container.innerHTML = logs.length === 0
                ? '<div class="text-center text-muted p-2">No activity</div>'
                : logs.map(l => {
                    const taskName = l.task_name ? ` on "${escapeHtml(l.task_name)}"` : '';
                    return `
                        <div class="activity-item">
                            <span class="activity-user">${escapeHtml(l.first_name || 'System')}</span>
                            <span class="activity-action">${l.action.replace(/_/g, ' ')}${taskName}</span>
                            <div class="activity-time">${new Date(l.created_at).toLocaleString()}</div>
                        </div>
                    `;
                }).join('');
        }
    } catch (err) {
        container.innerHTML = '<div class="text-center text-danger p-2">Error</div>';
    }

    new bootstrap.Modal(document.getElementById('projectActivityModal')).show();
});

document.getElementById('createTaskBtn')?.addEventListener('click', () => {
    if (!currentProjectId) {
        showNotification('warning', 'Select Project', 'Please select a project first');
        return;
    }
    new bootstrap.Modal(document.getElementById('createTaskModal')).show();
});
