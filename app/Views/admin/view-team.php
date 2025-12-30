<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>View Teams</h1>
        </div>
        <div class="page-nav">
            <a href="/admin/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="filters-container">
        <form id="filtersForm" class="filtersForm">
            <div class="search-bar">
                <input type="text" name="search" id="search_input" placeholder="Search managers..." style="width:250px;">
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
                    <th>Manager Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Team Members</th>
                    <th>Projects</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="main-table"></tbody>
        </table>
    </div>
    <div id="paginationContainer" class="pagination-wrapper"></div>
</main>

<!-- View Team Modal -->
<div class="modal fade" id="viewTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Team Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Manager Info -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Manager Information</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Name:</strong> <span id="view_manager_name"></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Email:</strong> <span id="view_manager_email"></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Phone:</strong> <span id="view_manager_phone"></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Team Size:</strong> <span class="badge bg-primary" id="view_team_count"></span>
                                <strong class="ms-2">Projects:</strong> <span class="badge bg-info" id="view_project_count"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Members Table -->
                <h6>Team Members</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Assigned Date</th>
                            </tr>
                        </thead>
                        <tbody id="team-members-table"></tbody>
                    </table>
                </div>
                <div id="teamPaginationContainer" class="pagination-wrapper"></div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/assets/js/admin/view-team.js"></script>