<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>My Projects</h1>
        </div>
        <div class="page-nav">
            <button data-bs-toggle="modal" data-bs-target="#createProjectModal" class="btn btn-primary">Create Project</button>
            <a href="/manager/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="filters-container">
        <form id="filtersForm" class="filtersForm">
            <div class="search-bar">
                <input type="text" name="search" id="search_input" placeholder="Search projects..." maxlength="128" style="width:250px;">
            </div>
            <div class="filters-selection">
                <select name="status_id" id="status_filter">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $status) { ?>
                        <option value="<?= $status['id'] ?>"><?= ucfirst(str_replace('_', ' ', $status['name'])) ?></option>
                    <?php } ?>
                </select>

                <select name="sort_by" id="sort_by">
                    <option value="">Sort by</option>
                    <option value="name">Name</option>
                    <option value="created_at" selected>Created At</option>
                    <option value="task_count">Tasks</option>
                </select>

                <select name="sort_order" id="sort_order">
                    <option value="ASC">Ascending</option>
                    <option value="DESC" selected>Descending</option>
                </select>

                <select name="limit" id="limit_filter">
                    <option value="10">Show 10</option>
                    <option value="25">Show 25</option>
                    <option value="50">Show 50</option>
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
                    <th>Project Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Tasks</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="main-table"></tbody>
        </table>
    </div>
    <div id="paginationContainer" class="pagination-wrapper"></div>
</main>

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createProjectForm" method="POST" action="/manager/project/create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Project Name *</label>
                        <input type="text" name="name" class="form-control" minlength="3" maxlength="128" pattern="(?=.*[A-Za-z0-9])[A-Za-z0-9 _()\-]{3,128}" title="3-128 chars. Letters, numbers, spaces, -, _, () only." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" maxlength="1000" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status_id" class="form-select" required>
                            <?php foreach ($statuses as $status) { ?>
                                <option value="<?= $status['id'] ?>"><?= ucfirst(str_replace('_', ' ', $status['name'])) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProjectForm">
                <input type="hidden" id="edit_project_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Project Name *</label>
                        <input type="text" id="edit_name" class="form-control" minlength="3" maxlength="128" pattern="(?=.*[A-Za-z0-9])[A-Za-z0-9 _()\-]{3,128}" title="3-128 chars. Letters, numbers, spaces, -, _, () only." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea id="edit_description" class="form-control" maxlength="1000" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select id="edit_status_id" class="form-select" required>
                            <?php foreach ($statuses as $status) { ?>
                                <option value="<?= $status['id'] ?>"><?= ucfirst(str_replace('_', ' ', $status['name'])) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateBtn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Employee Modal (Same UI as Admin) -->
<div class="modal fade" id="assignEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Project Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign_project_id">

                <!-- Project Info -->
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <strong>Project:</strong> <span id="assign_project_name"></span>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="assigned-tab" data-bs-toggle="tab"
                            data-bs-target="#assigned-pane" type="button" role="tab">
                            Assigned Employees
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="available-tab" data-bs-toggle="tab"
                            data-bs-target="#available-pane" type="button" role="tab">
                            Available to Assign
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="employeeTabsContent">
                    <!-- Assigned Employees Tab -->
                    <div class="tab-pane fade show active" id="assigned-pane" role="tabpanel">
                        <div class="filters-container mb-3">
                            <input type="text" id="assigned_search" class="form-control" maxlength="128"
                                placeholder="Search assigned employees..." style="max-width:300px;">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered align-middle table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Assigned At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="assigned-employees-table"></tbody>
                            </table>
                        </div>
                        <div id="assignedPaginationContainer" class="pagination-wrapper"></div>
                    </div>

                    <!-- Available Employees Tab -->
                    <div class="tab-pane fade" id="available-pane" role="tabpanel">
                        <div class="filters-container mb-3">
                            <input type="text" id="available_search" class="form-control" maxlength="128"
                                placeholder="Search available employees..." style="max-width:300px;">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered align-middle table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="available-employees-table"></tbody>
                            </table>
                        </div>
                        <div id="availablePaginationContainer" class="pagination-wrapper"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/assets/js/manager/projects.js"></script>