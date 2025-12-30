<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>My Team</h1>
        </div>
        <div class="page-nav">
            <button data-bs-toggle="modal" data-bs-target="#addMemberModal" class="btn btn-primary">Add Team Member</button>
            <a href="/manager/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="filters-container">
        <form id="filtersForm" class="filtersForm">
            <div class="search-bar">
                <input
                    type="text"
                    name="search"
                    id="search_input"
                    placeholder="Search team members..."
                    style="width:250px;">
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
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Assigned At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="main-table">
            </tbody>
        </table>
    </div>

    <div id="paginationContainer" class="pagination-wrapper"></div>
</main>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Team Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMemberForm" method="POST" action="/manager/team/add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Employee *</label>
                        <select name="employee_id" class="form-select" id="employee_select" required>
                            <option value="" disabled selected>Select an employee</option>
                            <?php foreach ($unassignedEmployees as $emp) { ?>
                                <option value="<?= $emp['id'] ?>">
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                    (<?= htmlspecialchars($emp['email']) ?>)
                                </option>
                            <?php } ?>
                        </select>
                        <small class="text-muted">Only unassigned employees are shown</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addBtn">Add to Team</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="module" src="/assets/js/manager/team.js"></script>