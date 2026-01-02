import initializeListPage from "/assets/js/list.js";
import { renderPagination } from "/assets/js/pagination.js";

function renderManagerRow({
    row,
    rowNumber
}) {
    const managerName = `${row.first_name} ${row.last_name || ''}`;
    const managerData = JSON.stringify({
        id: row.manager_id,
        name: managerName,
        email: row.email,
        phone: row.phone_number || '-',
        teamCount: row.team_count,
        projectCount: row.project_count
    }).replace(/"/g, '&quot;');

    return `
        <tr>
            <td>${rowNumber}</td>
            <td>${managerName}</td>
            <td>${row.email}</td>
            <td>${row.phone_number || '-'}</td>
            <td class="text-center">
                <span class="badge bg-primary">${row.team_count}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-info">${row.project_count}</span>
            </td>
            <td>
                <button class="btn btn-primary btn-sm view-team-btn" data-manager="${managerData}">
                    <i class="bi bi-people"></i> View Team
                </button>
                <a href="/admin/team-assignments?manager_id=${row.manager_id}" class="btn btn-warning btn-sm ms-1">
                    <i class="bi bi-person-gear"></i> Manage Team
                </a>
                <a href="/admin/projects?manager_id=${row.manager_id}" class="btn btn-info btn-sm ms-1">
                    <i class="bi bi-folder"></i> View Projects
                </a>
            </td>
        </tr>
    `;
}

const managerList = initializeListPage({
    apiEndpoint: "/api/admin/view-team/managers",
    renderRow: renderManagerRow,
    columnCount: 7,
});

// View Team Modal
let currentTeamPage = 1;
let currentManagerId = null;

document.addEventListener('click', async e => {
    const btn = e.target.closest('.view-team-btn');
    if (!btn) return;

    const manager = JSON.parse(btn.dataset.manager);
    currentManagerId = manager.id;
    currentTeamPage = 1;

    // Set manager info
    document.getElementById('view_manager_name').textContent = manager.name;
    document.getElementById('view_manager_email').textContent = manager.email;
    document.getElementById('view_manager_phone').textContent = manager.phone;
    document.getElementById('view_team_count').textContent = manager.teamCount;
    document.getElementById('view_project_count').textContent = manager.projectCount;

    // Load team members
    await loadTeamMembers(manager.id, 1);

    new bootstrap.Modal(document.getElementById('viewTeamModal')).show();
});

async function loadTeamMembers(managerId, page = 1) {
    const tbody = document.getElementById('team-members-table');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

    try {
        const response = await fetch(`/api/admin/view-team/team-members?search=&limit=10&page=${page}&manager_id=${managerId}`);
        const result = await response.json();

        if (!result.success || !result.data.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No team members found</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        result.data.data.forEach((member, index) => {
            const rowNumber = (page - 1) * 10 + index + 1;
            const statusBadge = member.is_active ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-danger">Inactive</span>';

            tbody.innerHTML += `
                <tr>
                    <td>${rowNumber}</td>
                    <td>${member.first_name} ${member.last_name || ''}</td>
                    <td>${member.email}</td>
                    <td>${member.phone_number || '-'}</td>
                    <td>${statusBadge}</td>
                    <td>${new Date(member.assigned_at).toLocaleDateString()}</td>
                </tr>
            `;
        });

        // Render pagination
        const paginationContainer = document.getElementById('teamPaginationContainer');
        renderPagination(result.data.pagination, paginationContainer, (newPage) => {
            currentTeamPage = newPage;
            loadTeamMembers(managerId, newPage);
        });
    } catch (error) {
        console.error('Failed to load team members:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading team members</td></tr>';
    }
}