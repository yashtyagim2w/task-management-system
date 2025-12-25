export default function renderUsersRow({ row, rowNumber }) {
    const lastName = row.last_name ? row.last_name : '-';
    const activeStatusRow = row.is_active ? '<span class="badge rounded-pill bg-success">Active</span>' : '<span class="badge rounded-pill bg-danger">Inactive</span>';
    return `
        <tr>
            <td>${rowNumber}</td>
            <td>${row.first_name}</td>
            <td>${lastName}</td>
            <td>${row.email}</td>
            <td>${row.phone_number}</td>
            <td class="text-capitalize">${row.role_name}</td>
            <td class="text-center">${activeStatusRow}</td>
            <td>${row.created_at}</td>
            <td>
                <a href="/admin/user/edit?userId=${row.id}" class="btn btn-primary">Edit</a>
            </td>
        </tr>
    `;
}
