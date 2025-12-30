export default function renderUsersRow({ row, rowNumber }) {
    console.log(row);
    const lastName = row.last_name ? row.last_name : '-';
    const activeStatusRow = row.is_active ? '<span class="badge rounded-pill bg-success">Active</span>' : '<span class="badge rounded-pill bg-danger">Inactive</span>';
    const isManager = row.role_id === 2;
    const managerName = row.manager_id !== '-' ? row.manager_first_name + ' ' + row.manager_last_name : '-';
    const isManagerExist = row.manager_id !== '-' ? true : false;
    return `
        <tr>
            <td>${rowNumber}</td>
            <td>${row.first_name}</td>
            <td class="${!row.last_name ? 'text-center' : ''}">${lastName}</td>
            <td>${row.email}</td>
            <td>${row.phone_number}</td>
            <td class="text-capitalize">${row.role_name}</td>
            <td class="${isManagerExist ? '' : 'text-center'}">${managerName}</td>
            <td class="${isManagerExist ? '' : 'text-center'}">${row.manager_email}</td>
            <td class="text-center">${activeStatusRow}</td>
            <td>${row.created_at}</td>
            <td>
                <button 
                    class="btn btn-sm btn-primary edit-user-btn"
                    data-user='${JSON.stringify(row)}'
                >
                    Edit
                </button>
            </td>
        </tr>
    `;
}
