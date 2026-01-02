import initializeListPage from "/assets/js/list.js";
import renderUsersRow from "/assets/js/users-render.js";

// Initialize List Page
const userList = initializeListPage({
    apiEndpoint: "/api/admin/users",
    renderRow: renderUsersRow,
    columnCount: 11,
});

// Function to load managers
async function loadManagers(selectElement, excludeUserId = null) {
    try {
        const response = await fetch('/api/admin/managers');
        const result = await response.json();

        if (result.success && result.data) {
            selectElement.innerHTML = '<option value="">Select Manager</option>';
            result.data.forEach(manager => {
                if (excludeUserId && manager.id === excludeUserId) return;
                const option = document.createElement('option');
                option.value = manager.id;
                option.textContent = `${manager.first_name} ${manager.last_name || ''} (${manager.email})`;
                selectElement.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load managers:', error);
    }
}

// Check if role is employee and toggle manager field - CREATE FORM
document.getElementById('create_role_id').addEventListener('change', async function () {
    const selectedRole = this.options[this.selectedIndex].text.toLowerCase();
    const managerField = document.getElementById('create_manager_field');
    const managerSelect = document.getElementById('create_manager_id');

    if (selectedRole === 'employee') {
        managerField.style.display = 'block';
        managerSelect.setAttribute('required', 'required');
        await loadManagers(managerSelect);
    } else {
        managerField.style.display = 'none';
        managerSelect.removeAttribute('required');
        managerSelect.value = '';
    }
});

// Check if role is employee and toggle manager field - EDIT FORM
document.getElementById('edit_role_id').addEventListener('change', async function () {
    const selectedRole = this.options[this.selectedIndex].text.toLowerCase();
    const managerField = document.getElementById('edit_manager_field');
    const managerSelect = document.getElementById('edit_manager_id');
    const currentUserId = parseInt(document.getElementById('edit_user_id').value);

    if (selectedRole === 'employee') {
        managerField.style.display = 'block';
        managerSelect.setAttribute('required', 'required');
        await loadManagers(managerSelect, currentUserId);
    } else {
        managerField.style.display = 'none';
        managerSelect.removeAttribute('required');
        managerSelect.value = '';
    }
});

document.getElementById('createUserForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = this;
    const saveBtn = document.getElementById('saveBtn');
    const formData = new FormData(form);

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    // Show loading overlay
    Swal.fire({
        title: 'Creating User...',
        text: 'Please wait while we set up the account and send welcome email',
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

            saveBtn.disabled = false;
            saveBtn.innerText = 'Save';
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: result.message || 'User created successfully',
            timer: 1500,
            showConfirmButton: false
        });

        form.reset();
        document.getElementById('create_manager_field').style.display = 'none';

        const modalEl = document.getElementById('createUserModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
        userList.reloadCurrentPage();

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Please try again'
        });
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Save';
    }
});

// Edit User Modal Elements
document.addEventListener("click", async e => {
    const btn = e.target.closest(".edit-user-btn");
    if (!btn) return;

    const user = JSON.parse(btn.dataset.user);
    console.log(user);
    edit_user_id.value = user.id;
    edit_first_name.value = user.first_name;
    edit_last_name.value = user.last_name ?? "";
    edit_email.value = user.email;
    edit_phone.value = user.phone_number;
    edit_password.value = '';
    edit_role_id.value = Number(user.role_id);
    edit_is_active.value = user.is_active ? 1 : 0;

    // Check if user is employee and show manager field
    const selectedRole = edit_role_id.options[edit_role_id.selectedIndex].text.toLowerCase();
    const managerField = document.getElementById('edit_manager_field');
    const managerSelect = document.getElementById('edit_manager_id');

    if (selectedRole === 'employee') {
        managerField.style.display = 'block';
        managerSelect.setAttribute('required', 'required');
        await loadManagers(managerSelect, user.id);
        if (user.manager_id) {
            managerSelect.value = user.manager_id;
        }
    } else {
        managerField.style.display = 'none';
        managerSelect.removeAttribute('required');
    }

    new bootstrap.Modal(editUserModal).show();
});

// Handle Edit User Form Submission
document.getElementById('editUserForm').addEventListener("submit", async e => {
    e.preventDefault();

    const payload = {
        user_id: Number(edit_user_id.value),
        first_name: edit_first_name.value.trim(),
        last_name: edit_last_name.value.trim(),
        email: edit_email.value.trim(),
        phone: edit_phone.value.trim(),
        password: edit_password.value,
        role_id: Number(edit_role_id.value),
        is_active: Number(edit_is_active.value)
    };

    // Add manager_id only if employee role is selected
    const selectedRole = edit_role_id.options[edit_role_id.selectedIndex].text.toLowerCase();
    if (selectedRole === 'employee') {
        payload.manager_id = edit_manager_id.value ? Number(edit_manager_id.value) : null;
    }

    updateBtn.disabled = true;
    updateBtn.innerText = "Updating...";

    try {
        const res = await fetch(`/api/admin/user`, {
            method: "PATCH",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (!res.ok || !result.success) {
            Swal.fire("Error", result.message || "Update failed", "error");
            return;
        }

        Swal.fire({
            icon: "success",
            title: "Updated",
            text: result.message || "User updated",
            timer: 1500,
            showConfirmButton: false
        });

        bootstrap.Modal.getInstance(editUserModal).hide();
        userList.reloadCurrentPage();

    } catch {
        Swal.fire("Network Error", "Please try again", "error");
    } finally {
        updateBtn.disabled = false;
        updateBtn.innerText = "Update";
    }
});