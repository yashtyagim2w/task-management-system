import initializeListPage from "/assets/js/list.js";

const statusColors = {
    'pending': 'secondary',
    'not_started': 'secondary',
    'in_progress': 'primary',
    'on_hold': 'warning',
    'completed': 'success'
};

function renderProjectRow({
    row,
    rowNumber
}) {
    const statusColor = statusColors[row.status_name] || 'secondary';
    const managerName = row.manager_first_name + ' ' + (row.manager_last_name || '');

    return `
        <tr>
            <td>${rowNumber}</td>
            <td>${row.name}</td>
            <td>${row.description ? row.description.substring(0, 40) + '...' : '-'}</td>
            <td>${managerName}</td>
            <td><span class="badge bg-${statusColor}">${row.status_name.replace('_', ' ')}</span></td>
            <td><span class="badge bg-info">${row.task_count || 0}</span></td>
            <td>${new Date(row.created_at).toLocaleDateString()}</td>
        </tr>
    `;
}

initializeListPage({
    apiEndpoint: "/api/employee/projects",
    renderRow: renderProjectRow,
    columnCount: 7,
});