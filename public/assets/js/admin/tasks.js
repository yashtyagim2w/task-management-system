/**
 * Admin Tasks - Kanban Board Implementation
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
let canEdit = true; // Admin can always edit
let tasksData = {}; // Local state for tasks
let canDragDrop = false;

// DOM Elements
const projectSelect = document.getElementById('projectSelect');
const kanbanContainer = document.getElementById('kanbanContainer');
const emptyState = document.getElementById('emptyState');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initChatModule('/api/admin');
    initProjectSelector();
    initDragDrop('#kanbanContainer', handleStatusChange);
    initCardClick('#kanbanContainer', openTaskModal);
    initTaskForms();
    initChatForm();
    initActivityTabRefresh();
});

// Project selector
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

// Load Kanban board
async function loadKanbanBoard() {
    if (!currentProjectId) return;

    kanbanContainer.innerHTML = '<div class="text-center p-5">Loading...</div>';

    try {
        const res = await fetch(`/api/admin/tasks/kanban?project_id=${currentProjectId}`);
        const result = await res.json();

        if (!result.success) {
            showNotification('error', 'Error', result.message);
            return;
        }

        // Store in local state
        tasksData = result.data.tasks;
        canDragDrop = result.data.can_drag_drop;
        renderKanbanBoard('#kanbanContainer', tasksData, canDragDrop);

        // Load assignees for create form
        loadProjectAssignees();
    } catch (err) {
        showNotification('error', 'Network Error', 'Failed to load tasks');
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

// Load project assignees for dropdown
async function loadProjectAssignees() {
    if (!currentProjectId) return;

    try {
        const res = await fetch(`/api/admin/task/assignees?project_id=${currentProjectId}`);
        const result = await res.json();

        if (result.success) {
            const assigneeSelect = document.getElementById('create_assigned_to');
            const editAssigneeSelect = document.getElementById('edit_assigned_to');

            const options = '<option value="">Unassigned</option>' +
                result.data.assignees.map(a =>
                    `<option value="${a.id}">${a.first_name} ${a.last_name || ''}</option>`
                ).join('');

            if (assigneeSelect) assigneeSelect.innerHTML = options;
            if (editAssigneeSelect) editAssigneeSelect.innerHTML = options;
        }
    } catch (err) {
        console.error('Failed to load assignees:', err);
    }
}

// Handle drag-drop status change
async function handleStatusChange(taskId, statusName) {
    const statusId = getStatusId(statusName);

    try {
        const res = await fetch('/api/admin/task/status', {
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

// Open task detail modal
async function openTaskModal(taskId) {
    currentTaskId = parseInt(taskId);
    setCurrentTaskId(currentTaskId);

    try {
        const res = await fetch(`/api/admin/task/details?task_id=${taskId}`);
        const result = await res.json();

        if (!result.success) {
            showNotification('error', 'Error', result.message);
            return;
        }

        const task = result.data.task;
        const assignees = result.data.assignees;

        // Populate modal
        document.getElementById('taskDetailTitle').textContent = task.name;
        document.getElementById('taskDetailDescription').innerHTML = task.description ? escapeHtml(task.description) : '<em>No description</em>';
        document.getElementById('taskDetailProject').textContent = task.project_name;
        document.getElementById('taskDetailStatus').innerHTML = `<span class="badge bg-secondary">${task.status_name.replace('_', ' ')}</span>`;
        document.getElementById('taskDetailPriority').innerHTML = `<span class="badge bg-secondary">${task.priority_name}</span>`;
        document.getElementById('taskDetailDueDate').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'Not set';
        document.getElementById('taskDetailAssignee').textContent = task.assigned_first_name ?
            `${task.assigned_first_name} ${task.assigned_last_name || ''}` : 'Unassigned';
        document.getElementById('taskDetailCreatedAt').textContent = new Date(task.created_at).toLocaleString();

        // Populate edit form
        document.getElementById('edit_task_id').value = task.id;
        document.getElementById('edit_task_name').value = task.name;
        document.getElementById('edit_task_description').value = task.description || '';
        document.getElementById('edit_task_status_id').value = task.task_status_id;
        document.getElementById('edit_task_priority_id').value = task.task_priority_id;
        document.getElementById('edit_task_due_date').value = task.due_date || '';

        // Update assignee dropdown
        const editAssigneeSelect = document.getElementById('edit_assigned_to');
        editAssigneeSelect.innerHTML = '<option value="">Unassigned</option>' +
            assignees.map(a => `<option value="${a.id}" ${a.id == task.assigned_to ? 'selected' : ''}>${a.first_name} ${a.last_name || ''}</option>`).join('');

        // Load chat and activity
        loadTaskComments();
        loadTaskActivity();

        // Start chat auto-refresh every 2 seconds
        startChatAutoRefresh();

        // Show modal
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

// Initialize task forms
function initTaskForms() {
    // Create task form
    document.getElementById('createTaskForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.set('project_id', currentProjectId);

        // Validate due date
        const dueDate = formData.get('due_date');
        if (dueDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (new Date(dueDate) < today) {
                showNotification('error', 'Invalid Date', 'Due date cannot be in the past');
                return;
            }
        }

        try {
            const res = await fetch('/admin/task/create', {
                method: 'POST',
                body: formData
            });
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

    // Edit task form
    document.getElementById('editTaskForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validate due date
        const dueDate = document.getElementById('edit_task_due_date').value;
        if (dueDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (new Date(dueDate) < today) {
                showNotification('error', 'Invalid Date', 'Due date cannot be in the past');
                return;
            }
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

        try {
            const res = await fetch('/api/admin/task', {
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

    // Delete task
    document.getElementById('deleteTaskBtn')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: 'Delete Task?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Delete'
        });

        if (!confirmed.isConfirmed) return;

        try {
            const res = await fetch('/api/admin/task', {
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

// Project activity modal
document.getElementById('showProjectActivityBtn')?.addEventListener('click', async () => {
    if (!currentProjectId) return;

    const container = document.getElementById('projectActivityContainer');
    container.innerHTML = '<div class="text-center p-2">Loading...</div>';

    try {
        const res = await fetch(`/api/admin/project/activity-logs?project_id=${currentProjectId}`);
        const result = await res.json();

        if (result.success) {
            const logs = result.data.data;
            if (logs.length === 0) {
                container.innerHTML = '<div class="text-center text-muted p-2">No activity yet</div>';
            } else {
                container.innerHTML = logs.map(l => {
                    const taskName = l.task_name ? ` on "${escapeHtml(l.task_name)}"` : '';
                    return `
                        <div class="activity-item">
                            <span class="activity-user">${escapeHtml(l.first_name || 'System')} ${escapeHtml(l.last_name || '')}</span>
                            <span class="activity-action">${l.action.replace(/_/g, ' ')}${taskName}</span>
                            <div class="activity-time">${new Date(l.created_at).toLocaleString()}</div>
                        </div>
                    `;
                }).join('');
            }
        }
    } catch (err) {
        container.innerHTML = '<div class="text-center text-danger p-2">Error loading</div>';
    }

    new bootstrap.Modal(document.getElementById('projectActivityModal')).show();
});

// Open create modal (set project_id)
document.getElementById('createTaskBtn')?.addEventListener('click', () => {
    if (!currentProjectId) {
        showNotification('warning', 'Select Project', 'Please select a project first');
        return;
    }
    new bootstrap.Modal(document.getElementById('createTaskModal')).show();
});
