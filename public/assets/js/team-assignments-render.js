export default function renderAssignmentRow({row, rowNumber}) {
    return `
        <tr>
            <td>${rowNumber}</td>
            <td>${row.manager_first_name} ${row.manager_last_name || ''}</td>
            <td>${row.manager_email}</td>
            <td>${row.employee_first_name} ${row.employee_last_name || ''}</td>
            <td>${row.employee_email}</td>
            <td>${new Date(row.assigned_at).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-danger btn-sm remove-btn" 
                        data-manager-id="${row.manager_id}" 
                        data-employee-id="${row.employee_id}">
                    Remove
                </button>
            </td>
        </tr>
    `;
}
