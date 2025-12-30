<main>
    <div class="main-nav">
        <div class="nav-heading">
            <h1>My Assigned Projects</h1>
        </div>
        <div class="page-nav">
            <a href="/employee/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="filters-container">
        <form id="filtersForm" class="filtersForm">
            <div class="search-bar">
                <input type="text" name="search" id="search_input" placeholder="Search projects..." style="width:250px;">
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
                    <th>Manager</th>
                    <th>Status</th>
                    <th>Tasks</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody id="main-table"></tbody>
        </table>
    </div>
    <div id="paginationContainer" class="pagination-wrapper"></div>
</main>

<script type="module" src="/assets/js/employee/projects.js"></script>