import initializeListPage from "/assets/js/list.js";

// Render function for team members
function renderTeamRow({ row, rowNumber }) {
    const statusBadge = row.is_active ?
        '<span class="badge bg-success">Active</span>' :
        '<span class="badge bg-danger">Inactive</span>';

    const memberData = JSON.stringify(row).replace(/'/g, "\\'");

    return `
        <tr>
            <td>${rowNumber}</td>
            <td>${row.first_name}</td>
            <td>${row.last_name || '-'}</td>
            <td>${row.email}</td>
            <td>${row.phone_number || '-'}</td>
            <td>${row.project_count || 0}</td>
            <td>${row.task_count || 0}</td>
            <td>${statusBadge}</td>
            <td>${new Date(row.assigned_at).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-danger btn-sm remove-member-btn" data-user-id="${row.user_id}">
                    Remove
                </button>
            </td>
        </tr>
    `;
}

const teamList = initializeListPage({
    apiEndpoint: "/api/manager/team",
    renderRow: renderTeamRow,
    columnCount: 10,
});

// Add member form
document.getElementById('addMemberForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    const addBtn = document.getElementById('addBtn');
    const formData = new FormData(form);

    addBtn.disabled = true;
    addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding...';

    // Show loading overlay
    Swal.fire({
        title: 'Adding Team Member...',
        text: 'Please wait while we add the employee and send notification',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!response.ok || result.success === false) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message || 'Something went wrong'
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: result.message || 'Team member added',
            timer: 1500,
            showConfirmButton: false
        });

        form.reset();
        bootstrap.Modal.getInstance(document.getElementById('addMemberModal')).hide();
        teamList.reloadCurrentPage();

        // Refresh the page to get updated unassigned employees
        setTimeout(() => location.reload(), 1500);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Please try again'
        });
    } finally {
        addBtn.disabled = false;
        addBtn.innerText = 'Add to Team';
    }
});

// Remove member
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.remove-member-btn');
    if (!btn) return;

    const userId = btn.dataset.userId;

    const confirmResult = await Swal.fire({
        title: 'Remove team member?',
        text: 'This will remove the employee from your team.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove'
    });

    if (!confirmResult.isConfirmed) return;

    // Show loading overlay
    Swal.fire({
        title: 'Removing Team Member...',
        text: 'Please wait while we remove the employee and send notification',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const formData = new FormData();
        formData.append('employee_id', userId);

        const response = await fetch('/manager/team/remove', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            Swal.fire('Error', result.message || 'Failed to remove', 'error');
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Removed',
            timer: 1500,
            showConfirmButton: false
        });
        teamList.reloadCurrentPage();
    } catch {
        Swal.fire('Network Error', 'Please try again', 'error');
    }
});