import initializeListPage from "/assets/js/list.js";
import renderAssignmentRow from "/assets/js/team-assignments-render.js";

console.log(initializeListPage);
const assignmentList = initializeListPage({
    apiEndpoint: "/api/admin/team-assignments",
    renderRow: renderAssignmentRow,
    columnCount: 7,
});

// Load dropdown data via AJAX
async function loadDropdownData() {
    const managerSelect = document.getElementById('managerSelect');
    const employeeSelect = document.getElementById('employeeSelect');

    // Show loading state
    managerSelect.innerHTML = '<option value="">Loading managers...</option>';
    employeeSelect.innerHTML = '<option value="">Loading employees...</option>';
    managerSelect.disabled = true;
    employeeSelect.disabled = true;

    try {
        // Fetch managers and employees in parallel
        const [managersRes, employeesRes] = await Promise.all([
            fetch('/api/admin/team-assignments/managers', {
                headers: {
                    'Accept': 'application/json'
                }
            }),
            fetch('/api/admin/unassigned-employees', {
                headers: {
                    'Accept': 'application/json'
                }
            })
        ]);

        const managersData = await managersRes.json();
        const employeesData = await employeesRes.json();

        // Populate managers dropdown
        if (managersData.success && managersData.data.managers) {
            managerSelect.innerHTML = '<option value="">Select Manager</option>';
            managersData.data.managers.forEach(manager => {
                const option = document.createElement('option');
                option.value = manager.id;
                option.textContent = `${manager.first_name} ${manager.last_name || ''} (${manager.email})`;
                managerSelect.appendChild(option);
            });
        } else {
            managerSelect.innerHTML = '<option value="">No managers available</option>';
        }

        // Populate employees dropdown
        if (employeesData.success && employeesData.data.employees) {
            if (employeesData.data.employees.length === 0) {
                employeeSelect.innerHTML = '<option value="">No employees available</option>';
            } else {
                employeeSelect.innerHTML = '<option value="">Select Employee</option>';
                employeesData.data.employees.forEach(employee => {
                    const option = document.createElement('option');
                    option.value = employee.id;
                    let label = `${employee.first_name} ${employee.last_name || ''} (${employee.email})`;
                    if (employee.is_assigned && employee.current_manager_first_name) {
                        label += ` [Currently: ${employee.current_manager_first_name} ${employee.current_manager_last_name || ''}]`;
                    }
                    option.textContent = label;
                    employeeSelect.appendChild(option);
                });
            }
        } else {
            employeeSelect.innerHTML = '<option value="">Failed to load employees</option>';
        }
    } catch (error) {
        console.error('Error loading dropdown data:', error);
        managerSelect.innerHTML = '<option value="">Failed to load managers</option>';
        employeeSelect.innerHTML = '<option value="">Failed to load employees</option>';
    } finally {
        managerSelect.disabled = false;
        employeeSelect.disabled = false;
    }
}

// Load dropdown data when modal opens
document.getElementById('assignModal').addEventListener('show.bs.modal', loadDropdownData);

// Assign form
document.getElementById('assignForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    const assignBtn = document.getElementById('assignBtn');
    const formData = new FormData(form);

    assignBtn.disabled = true;
    assignBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Assigning...';

    // Show loading overlay
    Swal.fire({
        title: 'Assigning Employee...',
        text: 'Please wait while we set up the team assignment and send notification',
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
        bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
        assignmentList.reloadCurrentPage();
    } catch {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Please try again'
        });
    } finally {
        assignBtn.disabled = false;
        assignBtn.innerText = 'Assign';
    }
});

// Remove
document.addEventListener('click', async e => {
    const btn = e.target.closest('.remove-btn');
    if (!btn) return;

    const confirmResult = await Swal.fire({
        title: 'Remove assignment?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove'
    });

    if (!confirmResult.isConfirmed) return;

    // Show loading overlay
    Swal.fire({
        title: 'Removing from Team...',
        text: 'Please wait while we remove the employee and send notification',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const formData = new FormData();
        formData.append('manager_id', btn.dataset.managerId);
        formData.append('employee_id', btn.dataset.employeeId);

        const res = await fetch('/api/admin/team/remove', {
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
            timer: 1500,
            showConfirmButton: false
        });
        assignmentList.reloadCurrentPage();
    } catch {
        Swal.fire('Network Error', 'Please try again', 'error');
    }
});