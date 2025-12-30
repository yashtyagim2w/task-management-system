<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>Team Assignments</h1>
        </div>
        <div class="page-nav">
            <button data-bs-toggle="modal" data-bs-target="#assignModal" class="btn btn-primary">Assign Employee to Manager</button>
            <a href="/admin/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="filters-container">
        <form id="filtersForm" class="filtersForm">
            <div class="search-bar">
                <input type="text" name="search" id="search_input" placeholder="Search..." style="width:250px;">
            </div>
            <div class="filters-selection">
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
                    <th>Manager</th>
                    <th>Manager Email</th>
                    <th>Employee</th>
                    <th>Employee Email</th>
                    <th>Assigned At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="main-table"></tbody>
        </table>
    </div>
    <div id="paginationContainer" class="pagination-wrapper"></div>
</main>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Employee to Manager</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignForm" method="POST" action="/api/admin/team/assign">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Manager *</label>
                        <select name="manager_id" id="managerSelect" class="form-select" required>
                            <option value="">Loading managers...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Employee *</label>
                        <select name="employee_id" id="employeeSelect" class="form-select" required>
                            <option value="">Loading employees...</option>
                        </select>
                        <small class="text-muted">Only unassigned employees are shown</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="assignBtn">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="module" src="/assets/js/admin/team-assignments.js"></script>