<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>User management</h1>
        </div>
        <div class="page-nav">
            <button data-bs-toggle="modal" data-bs-target="#createUserModal" class="btn btn-primary">Create New User</button>
            <a href="/admin/dashboard" class="btn btn-secondary">Back to Dashboard</a>
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
                    style="width:250px;">
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
                    <th>Manager Name</th>
                    <th>Manager Email</th>
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
                            required>
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
                            title="Only alphabets allowed">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            maxlength="128"
                            required>
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
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <div class="input-group">
                            <input
                                type="password"
                                name="password"
                                id="create_password"
                                class="form-control"
                                minlength="6"
                                maxlength="32"
                                required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('create_password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="role_id" id="create_role_id" class="form-select" required>
                            <option value="" disabled selected>Select Role</option>
                            <?php foreach ($roles as $role) { ?>
                                <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3" id="create_manager_field" style="display: none;">
                        <label class="form-label">Manager *</label>
                        <select name="manager_id" id="create_manager_id" class="form-select">
                            <option value="">Select Manager</option>
                            <!-- Managers will be loaded dynamically -->
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
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
                            required>
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
                            title="Only alphabets allowed">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input
                            type="email"
                            id="edit_email"
                            class="form-control"
                            maxlength="128"
                            required>
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
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="edit_password"
                                class="form-control"
                                minlength="6"
                                maxlength="32">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('edit_password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
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

                    <div class="mb-3" id="edit_manager_field" style="display: none;">
                        <label class="form-label">Manager *</label>
                        <select id="edit_manager_id" class="form-select">
                            <option value="">Select Manager</option>
                            <!-- Managers will be loaded dynamically -->
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
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="updateBtn">
                        Update
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="/assets/js/utils.js"></script>
<script type="module" src="/assets/js/admin/users.js"></script>