import { renderPagination } from "./pagination.js";

// Initialize list page with filtering, searching, pagination
// config: { apiEndpoint, renderRow, columnCount }
export default function initializeListPage(config) {
    const { apiEndpoint, renderRow, columnCount } = config;

    const filtersForm = document.getElementById("filtersForm");
    const tableBody = document.getElementById("main-table");
    const paginationContainer = document.getElementById("paginationContainer");

    let currentPage = 1;

    function debounce(fn, delay) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    async function fetchData() {
        try {
            const params = new URLSearchParams(new FormData(filtersForm));

            params.set("page", currentPage);

            const res = await fetch(`${apiEndpoint}?${params}`);
            
            if (!res.ok){
                throw new Error("Network error");
            } 

            const {success, data} = await res.json();
            if (!success){
                throw new Error("Failed to fetch data");
            };
            renderTable(data.data, data.pagination);

        } catch (err) {
            console.error(err);
            showMessage("Error loading data", "red");
        }
    }

    function showMessage(msg, color = "black") {
        tableBody.innerHTML = `
            <tr>
                <td colspan="${columnCount}" style="text-align:center; color:${color};">${msg}</td>
            </tr>
        `;
    }

    function reload() {
        currentPage = 1;
        fetchData();
    }

    function reloadCurrentPage() {
        fetchData();
    }

    function renderTable(data, pagination) {
        tableBody.innerHTML = "";

        renderPagination(pagination, paginationContainer, (newPage) => {
            currentPage = newPage;
            reloadCurrentPage();
        });

        if (!data.length) {
            showMessage("No records found.");
            return;
        }

        data.forEach((row, index) => {
            const rowNumber = index + 1 + ((pagination.page - 1) * pagination.limit);

            tableBody.innerHTML += renderRow({
                row,
                rowNumber,  
                pagination
            });
        });
    }

    // Debounce search
    const searchInput = document.getElementById("search_input");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(fetchData, 300));
    }

    // Filters
    [...filtersForm.querySelectorAll("select")].forEach(sel => {
        sel.addEventListener("change", () => {
            reload();
        });
    });

    // Reset button
    const resetBtn = document.getElementById("resetBtn");
    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            filtersForm.reset();
            reload();
        });
    }

    // Initial fetch 
    fetchData();

    return {
        reload,
        reloadCurrentPage
    }
}
