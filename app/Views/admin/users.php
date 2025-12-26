<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>User management</h1>
        </div>
        <div class="page-nav">
            <button data-bs-toggle="modal" data-bs-target="#createUserModal" class="btn btn-primary">Create New User</button>
            <a href="/admin/dashboard" class="btn btn-primary">back to dashboard</a>
        </div>
    </div>

    <div class="filters-container">
        <form id="filtersForm" class="filtersForm">
            <div class="search-bar">
                <input 
                    type="text" 
                    name="search" 
                    id="search_input"
                    placeholder="Search records..."
                    style="width:250px;"
                >
            </div>

            <div class="filters-selection">
                <select name="role_id" id="role_filter">
                    <option value="">All Roles</option>
                    <?php foreach($roles as $role) { ?>
                        <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                    <?php } ?>
                </select>

                <select name="active_status_id" id="active_status_filter">
                    <option value="">All Statuses</option>
                    <?php foreach($activeStatus as $status) { ?>
                        <option value="<?= $status['id'] ?>"><?= $status['name'] ?></option>
                    <?php } ?>
                </select>

                <select name="sort_by" id="sort_by">
                    <option value="">Sort by</option>
                    <option value="first_name">First Name</option>
                    <option value="last_name">Last Name</option>
                    <option value="email">Email</option>
                    <option value="created_at">Created At</option>
                </select>

                <select name="sort_order" id="sort_order">
                    <option value="ASC" selected>Ascending</option>
                    <option value="DESC">Descending</option>
                </select>

                <select name="limit" id="limit_filter">
                    <option value="10">Show 10</option>
                    <option value="25">Show 25</option>
                    <option value="50">Show 50</option>
                    <option value="100">Show 100</option>
                </select>

                <button type="button" class="btn btn-danger" id="resetBtn">Reset</button>
            </div>
        </form>
    </div>


    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Active Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="main-table">
        
            </tbody>
        </table>
    </div>

    <div id="paginationContainer" class="pagination-wrapper"></div>
</main>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="createUserForm" method="POST" action="/admin/user/create">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">First Name *</label>
                        <input
                            type="text"
                            name="first_name"
                            class="form-control"
                            minlength="2" 
                            maxlength="128" 
                            pattern="[A-Za-z]+" 
                            title="Only alphabets allowed"
                            required
                        >
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input
                            type="text"
                            name="last_name"
                            class="form-control"
                            minlength="1" 
                            maxlength="128" 
                            pattern="[A-Za-z]+" 
                            title="Only alphabets allowed"
                        >
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            maxlength="128"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            pattern="\d{10}" 
                            title="Only numbers are allowed." 
                            minlength="10"
                            maxlength="10"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="6" 
                            maxlength="32"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="role_id" class="form-select" required>
                            <option value="" disabled selected>Select Role</option>
                            <?php foreach($roles as $role) { ?>
                                <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="editUserForm">
                <input type="hidden" id="edit_user_id">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">First Name *</label>
                        <input 
                            type="text" 
                            id="edit_first_name" 
                            class="form-control" 
                            minlength="2" 
                            maxlength="128" 
                            pattern="[A-Za-z]+" 
                            title="Only alphabets allowed"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input 
                            type="text" 
                            id="edit_last_name" 
                            class="form-control"
                            minlength="2" 
                            maxlength="128" 
                            pattern="[A-Za-z]+" 
                            title="Only alphabets allowed"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input 
                            type="email" 
                            id="edit_email" 
                            class="form-control" 
                            maxlength="128"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input 
                            type="text" 
                            id="edit_phone" 
                            class="form-control" 
                            pattern="\d{10}" 
                            title="Only numbers are allowed." 
                            minlength="10"
                            maxlength="10"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input 
                            type="text" 
                            id="edit_password" 
                            class="form-control"  
                            minlength="6"
                            maxlength="32"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select id="edit_role_id" class="form-select" required>
                            <?php foreach ($roles as $role) { ?>
                                <option value="<?= $role['id'] ?>">
                                    <?= $role['name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select id="edit_is_active" class="form-select" required>
                            <?php foreach ($activeStatus as $status) { ?>
                                <option value="<?= $status['id'] ?>">
                                    <?= $status['name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button 
                        type="button" 
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >   
                        Cancel
                    </button>
                    
                    <button 
                        type="submit" 
                        class="btn btn-primary" 
                        id="updateBtn"
                    >
                        Update
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script type="module">
    import initializeListPage from "/assets/js/list.js";
    import renderUsersRow from "/assets/js/users-render.js";

    // Initialize List Page
    const userList = initializeListPage({
        apiEndpoint: "/api/admin/users",
        renderRow: renderUsersRow,
        columnCount: 9,
    });

    document.getElementById('createUserForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = this;
        const saveBtn = document.getElementById('saveBtn');
        const formData = new FormData(form);

        saveBtn.disabled = true;
        saveBtn.innerText = 'Saving...';

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
    document.addEventListener("click", e => {
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
</script>
