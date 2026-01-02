import initializeListPage from "/assets/js/list.js";
import {
    renderPagination
} from "/assets/js/pagination.js";

const statusColors = {
    'pending': 'secondary',
    'not_started': 'secondary',
    'in_progress': 'primary',
    'on_hold': 'warning',
    'completed': 'success'
};

// Helper to decode HTML entities for edit forms
function decodeHtmlEntities(str) {
    if (!str) return '';
    const textarea = document.createElement('textarea');
    textarea.innerHTML = str;
    return textarea.value;
}

function renderProjectRow({
    row,
    rowNumber
}) {
    const statusColor = statusColors[row.status_name] || 'secondary';
    const projectData = JSON.stringify(row).replace(/"/g, '&quot;');
    const managerName = row.manager_first_name + ' ' + (row.manager_last_name || '');
    const deletedBadge = row.is_deleted == 1 ? '<span class="badge bg-danger ms-1">Deleted</span>' : '';

    return `
        <tr class="${row.is_deleted == 1 ? 'table-secondary' : ''}">
            <td>${rowNumber}</td>
            <td>${row.name}${deletedBadge}</td>
            <td>${row.description ? row.description.substring(0, 40) + '...' : '-'}</td>
            <td>${managerName}</td>
            <td><span class="badge bg-${statusColor}">${row.status_name.replace('_', ' ')}</span></td>
            <td><span class="badge bg-info">${row.task_count || 0}</span></td>
            <td>${new Date(row.created_at).toLocaleDateString()}</td>
            <td>
                <a href="/admin/tasks?project_id=${row.id}" class="btn btn-primary btn-sm" title="View Tasks">
                    Tasks
                </a>
                <button class="btn btn-warning btn-sm edit-project-btn" data-project="${projectData}" title="Edit">
                    Edit
                </button>
                <button class="btn btn-info btn-sm assign-employee-btn" data-project="${projectData}" title="Assign Employees">
                    Assign
                </button>
                ${row.is_deleted == 0 ? `
                    <button class="btn btn-danger btn-sm delete-project-btn" data-id="${row.id}" title="Delete">
                        Delete
                    </button>
                ` : `
                    <button class="btn btn-success btn-sm recover-project-btn" data-id="${row.id}" title="Recover">
                        Recover
                    </button>
                `}
            </td>
        </tr>
    `;
}

const projectList = initializeListPage({
    apiEndpoint: "/api/admin/projects",
    renderRow: renderProjectRow,
    columnCount: 8,
});

// Validation patterns
const nameRegex = /^(?=.*[A-Za-z0-9])[A-Za-z0-9 _()\-]{3,128}$/;
const descRegex = /^[A-Za-z0-9 _.,:;'"!?()\-\n\r]{0,1000}$/;

// Create project
document.getElementById('createProjectForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    const saveBtn = document.getElementById('saveBtn');
    const formData = new FormData(form);

    const projectName = formData.get('name').trim();
    const description = formData.get('description').trim();

    // Frontend validation
    if (!nameRegex.test(projectName)) {
        Swal.fire('Invalid Name', 'Project name must be 3-128 characters using only letters, numbers, spaces, underscores, parentheses, and hyphens.', 'error');
        return;
    }
    if (description && !descRegex.test(description)) {
        Swal.fire('Invalid Description', 'Description contains disallowed characters.', 'error');
        return;
    }

    saveBtn.disabled = true;
    saveBtn.innerText = 'Creating...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: result.message,
            timer: 1500,
            showConfirmButton: false
        });
        form.reset();
        bootstrap.Modal.getInstance(document.getElementById('createProjectModal')).hide();
        projectList.reloadCurrentPage();
    } catch {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Please try again'
        });
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Create';
    }
});

// Edit modal
document.addEventListener('click', e => {
    const btn = e.target.closest('.edit-project-btn');
    if (!btn) return;
    const project = JSON.parse(btn.dataset.project);
    document.getElementById('edit_project_id').value = project.id;
    document.getElementById('edit_name').value = decodeHtmlEntities(project.name);
    document.getElementById('edit_description').value = decodeHtmlEntities(project.description);
    document.getElementById('edit_manager_id').value = project.manager_id;
    document.getElementById('edit_status_id').value = project.project_status_id;
    new bootstrap.Modal(document.getElementById('editProjectModal')).show();
});

// Update
document.getElementById('editProjectForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const updateBtn = document.getElementById('updateBtn');
    const projectName = document.getElementById('edit_name').value.trim();
    const description = document.getElementById('edit_description').value.trim();

    // Frontend validation
    if (!nameRegex.test(projectName)) {
        Swal.fire('Invalid Name', 'Project name must be 3-128 characters using only letters, numbers, spaces, underscores, parentheses, and hyphens.', 'error');
        return;
    }
    if (description && !descRegex.test(description)) {
        Swal.fire('Invalid Description', 'Description contains disallowed characters.', 'error');
        return;
    }

    const payload = {
        project_id: parseInt(document.getElementById('edit_project_id').value),
        name: projectName,
        description: description,
        manager_id: parseInt(document.getElementById('edit_manager_id').value),
        status_id: parseInt(document.getElementById('edit_status_id').value)
    };

    updateBtn.disabled = true;
    updateBtn.innerText = 'Updating...';

    try {
        const res = await fetch('/api/admin/project', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (!res.ok || !result.success) {
            Swal.fire('Error', result.message, 'error');
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Updated',
            timer: 1500,
            showConfirmButton: false
        });
        bootstrap.Modal.getInstance(document.getElementById('editProjectModal')).hide();
        projectList.reloadCurrentPage();
    } catch {
        Swal.fire('Network Error', 'Please try again', 'error');
    } finally {
        updateBtn.disabled = false;
        updateBtn.innerText = 'Update';
    }
});

// Delete
document.addEventListener('click', async e => {
    const btn = e.target.closest('.delete-project-btn');
    if (!btn) return;

    const confirmResult = await Swal.fire({
        title: 'Delete project?',
        text: 'This action can be reversed by admin.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
    });

    if (!confirmResult.isConfirmed) return;

    try {
        const res = await fetch('/api/admin/project', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                project_id: parseInt(btn.dataset.id)
            })
        });
        const result = await res.json();

        if (!res.ok || !result.success) {
            Swal.fire('Error', result.message, 'error');
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Deleted',
            timer: 1500,
            showConfirmButton: false
        });
        projectList.reloadCurrentPage();
    } catch {
        Swal.fire('Network Error', 'Please try again', 'error');
    }
});

// Recover project
document.addEventListener('click', async e => {
    const btn = e.target.closest('.recover-project-btn');
    if (!btn) return;

    const confirmResult = await Swal.fire({
        title: 'Recover project?',
        text: 'This will restore the deleted project.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, recover'
    });

    if (!confirmResult.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('project_id', btn.dataset.id);

        const res = await fetch('/api/admin/project/recover', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });
        const result = await res.json();

        if (!res.ok || !result.success) {
            Swal.fire('Error', result.message, 'error');
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Recovered',
            timer: 1500,
            showConfirmButton: false
        });
        projectList.reloadCurrentPage();
    } catch {
        Swal.fire('Network Error', 'Please try again', 'error');
    }
});

// ========== Employee Assignment Modal ==========
let currentProjectId = null;
let assignedPage = 1;
let availablePage = 1;

document.addEventListener('click', e => {
    const btn = e.target.closest('.assign-employee-btn');
    if (!btn) return;
    const project = JSON.parse(btn.dataset.project);
    currentProjectId = project.id;
    document.getElementById('assign_project_id').value = project.id;
    document.getElementById('assign_project_name').textContent = project.name;
    document.getElementById('assign_manager_name').textContent =
        project.manager_first_name + ' ' + (project.manager_last_name || '');

    assignedPage = 1;
    availablePage = 1;
    loadAssignedEmployees();
    loadAvailableEmployees();

    new bootstrap.Modal(document.getElementById('assignEmployeeModal')).show();
});

// Search handlers with debounce
let assignedSearchTimeout, availableSearchTimeout;
document.getElementById('assigned_search').addEventListener('input', () => {
    clearTimeout(assignedSearchTimeout);
    assignedSearchTimeout = setTimeout(() => {
        assignedPage = 1;
        loadAssignedEmployees();
    }, 300);
});
document.getElementById('available_search').addEventListener('input', () => {
    clearTimeout(availableSearchTimeout);
    availableSearchTimeout = setTimeout(() => {
        availablePage = 1;
        loadAvailableEmployees();
    }, 300);
});

async function loadAssignedEmployees() {
    const tbody = document.getElementById('assigned-employees-table');
    const search = document.getElementById('assigned_search').value;
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

    try {
        const res = await fetch(`/api/admin/project/assignees?project_id=${currentProjectId}&search=${encodeURIComponent(search)}&page=${assignedPage}&limit=10`);
        const result = await res.json();

        if (!result.success || !result.data.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No employees assigned</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        result.data.data.forEach((emp, idx) => {
            tbody.innerHTML += `
                <tr>
                    <td>${(assignedPage - 1) * 10 + idx + 1}</td>
                    <td>${emp.first_name} ${emp.last_name || ''}</td>
                    <td>${emp.email}</td>
                    <td>${emp.phone_number || '-'}</td>
                    <td>${new Date(emp.assigned_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-employee-btn" 
                                data-user-id="${emp.id}" data-name="${emp.first_name} ${emp.last_name || ''}">
                            Remove
                        </button>
                    </td>
                </tr>
            `;
        });

        renderPagination(result.data.pagination, document.getElementById('assignedPaginationContainer'), (page) => {
            assignedPage = page;
            loadAssignedEmployees();
        });
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading</td></tr>';
    }
}

async function loadAvailableEmployees() {
    const tbody = document.getElementById('available-employees-table');
    const search = document.getElementById('available_search').value;
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';

    try {
        const res = await fetch(`/api/admin/project/assignable-employees?project_id=${currentProjectId}&search=${encodeURIComponent(search)}&page=${availablePage}&limit=10`);
        const result = await res.json();

        if (!result.success || !result.data.data.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No available employees</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        result.data.data.forEach((emp, idx) => {
            tbody.innerHTML += `
                <tr>
                    <td>${(availablePage - 1) * 10 + idx + 1}</td>
                    <td>${emp.first_name} ${emp.last_name || ''}</td>
                    <td>${emp.email}</td>
                    <td>${emp.phone_number || '-'}</td>
                    <td>
                        <button class="btn btn-success btn-sm add-employee-btn" 
                                data-user-id="${emp.user_id}" data-name="${emp.first_name} ${emp.last_name || ''}">
                            Assign
                        </button>
                    </td>
                </tr>
            `;
        });

        renderPagination(result.data.pagination, document.getElementById('availablePaginationContainer'), (page) => {
            availablePage = page;
            loadAvailableEmployees();
        });
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading</td></tr>';
    }
}

// Assign employee
document.addEventListener('click', async e => {
    const btn = e.target.closest('.add-employee-btn');
    if (!btn) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    // Show loading overlay
    Swal.fire({
        title: 'Assigning to Project...',
        text: 'Please wait while we assign the employee and send notification',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const userId = btn.dataset.userId;
    const formData = new FormData();
    formData.append('project_id', currentProjectId);
    formData.append('user_id', userId);

    try {
        const res = await fetch('/api/admin/project/assign-user', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });
        const result = await res.json();

        if (!res.ok || !result.success) {
            Swal.fire('Error', result.message, 'error');
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Assigned',
            timer: 1000,
            showConfirmButton: false
        });
        loadAssignedEmployees();
        loadAvailableEmployees();
    } catch {
        Swal.fire('Network Error', 'Please try again', 'error');
    } finally {
        btn.disabled = false;
    }
});

// Remove employee
document.addEventListener('click', async e => {
    const btn = e.target.closest('.remove-employee-btn');
    if (!btn) return;

    const confirm = await Swal.fire({
        title: `Remove ${btn.dataset.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove'
    });

    if (!confirm.isConfirmed) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    // Show loading overlay
    Swal.fire({
        title: 'Removing from Project...',
        text: 'Please wait while we remove the employee and send notification',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('project_id', currentProjectId);
    formData.append('user_id', btn.dataset.userId);

    try {
        const res = await fetch('/api/admin/project/remove-user', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });
        const result = await res.json();

        if (!res.ok || !result.success) {
            Swal.fire('Error', result.message, 'error');
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Removed',
            timer: 1000,
            showConfirmButton: false
        });
        loadAssignedEmployees();
        loadAvailableEmployees();
    } catch {
        Swal.fire('Network Error', 'Please try again', 'error');
    } finally {
        btn.disabled = false;
    }
});