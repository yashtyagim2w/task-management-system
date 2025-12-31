/**
 * Common Chat & Activity Module
 * Shared logic for ta`sk comments and activity logs
 */
import { formatActivityLog, formatChatMessage, showNotification } from '/assets/js/kanban.js';

let chatRefreshInterval = null;
let currentTaskIdRef = null;
let apiPrefix = '';
let chatState = '';
let firstLoad = true;
let isChatTabActive = true;

/**
 * Initialize the chat module with API prefix
 * @param {string} prefix - API prefix like '/api/admin', '/api/manager', '/api/employee'
 */
export function initChatModule(prefix) {
    apiPrefix = prefix;
}

/**
 * Set current task ID reference (call this when opening a task modal)
 */
export function setCurrentTaskId(taskId) {
    currentTaskIdRef = taskId;
    // Reset cache state for new task
    chatState = '';
    firstLoad = true;
    isChatTabActive = true; // Assume chat tab is active initially
}

/**
 * Get current task ID
 */
export function getCurrentTaskId() {
    return currentTaskIdRef;
}

/**
 * Start chat auto-refresh interval (only polls when chat tab is active)
 */
export function startChatAutoRefresh() {
    stopChatAutoRefresh();
    chatRefreshInterval = setInterval(() => {
        if (currentTaskIdRef && isChatTabActive) {
            loadTaskComments();
        }
    }, 2000);
}

/**
 * Stop chat auto-refresh interval
 */
export function stopChatAutoRefresh() {
    if (chatRefreshInterval) {
        clearInterval(chatRefreshInterval);
        chatRefreshInterval = null;
    }
}

/**
 * Load task comments (chat)
 */
export async function loadTaskComments() {
    if (!currentTaskIdRef) return;

    const container = document.getElementById('taskChatContainer');
    if (!container) return;

    if (firstLoad) {
        container.innerHTML = '<div class="text-center p-2">Loading...</div>';
        firstLoad = false;
    }

    try {
        const res = await fetch(`${apiPrefix}/task/comments?task_id=${currentTaskIdRef}`);
        const result = await res.json();

        // Compare stringified versions since objects are compared by reference, not value
        const resultStr = JSON.stringify(result);
        if (chatState === resultStr) {
            // No changes, skip re-render
            return;
        }
        chatState = resultStr;
        if (result.success) {
            const comments = result.data.comments;
            if (comments.length === 0) {
                container.innerHTML = '<div class="text-center text-muted p-2">No messages yet</div>';
            } else {
                container.innerHTML = comments.map(c => formatChatMessage(c)).join('');
                container.scrollTop = container.scrollHeight;
            }
        }
    } catch (err) {
        container.innerHTML = '<div class="text-center text-danger p-2">Error loading</div>';
    }
}

/**
 * Load task activity logs
 */
export async function loadTaskActivity() {
    if (!currentTaskIdRef) return;

    const container = document.getElementById('taskActivityContainer');
    if (!container) return;

    container.innerHTML = '<div class="text-center p-2">Loading...</div>';

    try {
        const res = await fetch(`${apiPrefix}/task/activity-logs?task_id=${currentTaskIdRef}`);
        const result = await res.json();

        if (result.success) {
            const logs = result.data.data;
            if (logs.length === 0) {
                container.innerHTML = '<div class="text-center text-muted p-2">No activity yet</div>';
            } else {
                container.innerHTML = logs.map(l => formatActivityLog(l)).join('');
            }
        }
    } catch (err) {
        container.innerHTML = '<div class="text-center text-danger p-2">Error loading</div>';
    }
}

/**
 * Initialize chat form submission
 */
export function initChatForm() {
    document.getElementById('taskChatForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('taskChatInput');
        const comment = input.value.trim();

        if (!comment || !currentTaskIdRef) return;

        const formData = new FormData();
        formData.append('task_id', currentTaskIdRef);
        formData.append('comment', comment);

        try {
            const res = await fetch(`${apiPrefix}/task/comment`, {
                method: 'POST',
                body: formData
            });
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
 * Initialize tab event handlers
 */
export function initActivityTabRefresh() {
    const chatTab = document.querySelector('[data-bs-target="#chatTab"]');
    const activityTab = document.querySelector('[data-bs-target="#activityTab"]');

    // When Chat tab is shown - start polling and scroll to bottom
    chatTab?.addEventListener('shown.bs.tab', () => {
        isChatTabActive = true;
        if (currentTaskIdRef) {
            loadTaskComments();
            setTimeout(() => {
                const container = document.getElementById('taskChatContainer');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        }
    });

    // When Chat tab is hidden - stop polling
    chatTab?.addEventListener('hidden.bs.tab', () => {
        isChatTabActive = false;
    });

    // When Activity tab is shown - refresh activity logs
    activityTab?.addEventListener('shown.bs.tab', () => {
        if (currentTaskIdRef) {
            loadTaskActivity();
        }
    });
}
