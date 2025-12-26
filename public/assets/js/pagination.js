export function renderPagination(pagination, container, onPageChange) {

    console.log('Rendering pagination:', pagination);
    console.log('Container:', container);
    console.log('onPageChange function:', onPageChange);
    const { page, totalPages } = pagination;
    container.innerHTML = "";
    container.className = "pagination-container";

    if (totalPages <= 1) return;

    const createBtn = (label, pageNum, disabled = false, isActive = false) => {
        const btn = document.createElement("button");
        btn.textContent = label;
        btn.disabled = disabled;
        btn.className = "pagination-btn";
        if (isActive) btn.classList.add("active");

        btn.addEventListener("click", () => {
            if (!disabled && pageNum !== page) {
                onPageChange(pageNum);
            }
        });

        return btn;
    };

    container.appendChild(
        createBtn("Prev", page - 1, page === 1)
    );

    for (let p = 1; p <= totalPages; p++) {
        const isActive = p === page;
        container.appendChild(
            createBtn(String(p), p, false, isActive)
        );
    }

    container.appendChild(
        createBtn("Next", page + 1, page === totalPages)
    );
}