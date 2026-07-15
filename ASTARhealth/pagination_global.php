<?php
// Pagination global untuk tabel dan daftar kartu: 10 data per halaman.
// Tampilan kontrol ringkas: ‹ 1/2 ›.
?>
<style>
    .astar-simple-pagination {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 16px;
        user-select: none;
    }

    .astar-simple-pagination .astar-page-button {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        background: #ffffff;
        color: #0d5db8;
        font-size: 22px;
        line-height: 1;
        box-shadow: 0 5px 14px rgba(15, 61, 130, 0.08);
        transition: 0.18s ease;
    }

    .astar-simple-pagination .astar-page-button:hover:not(:disabled) {
        background: #eef5ff;
        border-color: #9fc4ef;
        transform: translateY(-1px);
    }

    .astar-simple-pagination .astar-page-button:disabled {
        color: #aab4c1;
        background: #f5f7fa;
        cursor: not-allowed;
        box-shadow: none;
    }

    .astar-simple-pagination .astar-page-indicator {
        min-width: 58px;
        padding: 8px 12px;
        border-radius: 12px;
        background: #eef5ff;
        color: #0d5db8;
        font-weight: 700;
        text-align: center;
    }

    tr.astar-pagination-hidden,
    .astar-pagination-hidden,
    .astar-pagination-group-hidden {
        display: none !important;
    }
</style>
<script>
(function () {
    'use strict';

    if (window.__ASTAR_PAGINATION_LOADED__) return;
    window.__ASTAR_PAGINATION_LOADED__ = true;

    const ROWS_PER_PAGE = 10;
    const paginators = [];

    function createControls(target) {
        const controls = document.createElement('div');
        controls.className = 'astar-simple-pagination';
        controls.setAttribute('aria-label', 'Navigasi halaman data');
        controls.innerHTML = `
            <button type="button" class="astar-page-button astar-page-prev" aria-label="Halaman sebelumnya">&lsaquo;</button>
            <span class="astar-page-indicator">1/1</span>
            <button type="button" class="astar-page-button astar-page-next" aria-label="Halaman berikutnya">&rsaquo;</button>
        `;
        target.insertAdjacentElement('afterend', controls);
        return controls;
    }

    function bindNavigation(state, countItems, render) {
        state.prev.addEventListener('click', function () {
            if (state.currentPage > 1) {
                state.currentPage--;
                render(false);
                state.scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        state.next.addEventListener('click', function () {
            const totalPages = Math.max(1, Math.ceil(countItems() / ROWS_PER_PAGE));
            if (state.currentPage < totalPages) {
                state.currentPage++;
                render(false);
                state.scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    function isPlaceholderRow(row) {
        return row.cells.length === 1 && row.cells[0].colSpan > 1;
    }

    function isFilterVisible(element) {
        return element.style.display !== 'none' &&
            !element.hidden &&
            !element.classList.contains('d-none') &&
            element.dataset.astarFilteredOut !== '1';
    }

    function createTablePaginator(table) {
        if (!table || table.dataset.astarPaginationReady === '1') return null;
        if (table.matches('[data-no-pagination], .no-pagination')) return null;

        const tbody = table.tBodies && table.tBodies[0];
        if (!tbody) return null;

        table.dataset.astarPaginationReady = '1';
        const responsiveWrapper = table.closest('.table-responsive');
        const target = responsiveWrapper || table;
        const controls = createControls(target);

        const state = {
            currentPage: 1,
            prev: controls.querySelector('.astar-page-prev'),
            next: controls.querySelector('.astar-page-next'),
            indicator: controls.querySelector('.astar-page-indicator'),
            rendering: false,
            scrollTarget: table,
        };

        function getRows() {
            const allRows = Array.from(tbody.rows);
            const placeholderRows = allRows.filter(isPlaceholderRow);
            const summaryRows = allRows.filter(row => row.hasAttribute('data-pagination-summary'));
            const fixedRows = allRows.filter(row => row.hasAttribute('data-no-pagination-row'));
            const dataRows = allRows.filter(row =>
                !isPlaceholderRow(row) &&
                !row.hasAttribute('data-pagination-summary') &&
                !row.hasAttribute('data-no-pagination-row') &&
                isFilterVisible(row)
            );
            return { allRows, placeholderRows, summaryRows, fixedRows, dataRows };
        }

        function render(resetPage) {
            if (state.rendering) return;
            state.rendering = true;
            if (resetPage) state.currentPage = 1;

            const rows = getRows();
            const totalPages = Math.max(1, Math.ceil(rows.dataRows.length / ROWS_PER_PAGE));
            state.currentPage = Math.min(Math.max(state.currentPage, 1), totalPages);

            const start = (state.currentPage - 1) * ROWS_PER_PAGE;
            const visibleSet = new Set(rows.dataRows.slice(start, start + ROWS_PER_PAGE));

            rows.allRows.forEach(function (row) {
                let shouldShow = visibleSet.has(row);
                if (rows.placeholderRows.includes(row) || rows.fixedRows.includes(row)) shouldShow = true;
                if (rows.summaryRows.includes(row)) shouldShow = state.currentPage === totalPages;
                row.classList.toggle('astar-pagination-hidden', !shouldShow);
            });

            state.indicator.textContent = state.currentPage + '/' + totalPages;
            state.prev.disabled = state.currentPage <= 1;
            state.next.disabled = state.currentPage >= totalPages;
            state.rendering = false;
        }

        bindNavigation(state, function () { return getRows().dataRows.length; }, render);

        const observer = new MutationObserver(function (mutations) {
            const relevant = mutations.some(function (mutation) {
                return mutation.type === 'childList' ||
                    (mutation.type === 'attributes' && ['style', 'hidden', 'class'].includes(mutation.attributeName));
            });
            if (relevant && !state.rendering) window.setTimeout(function () { render(true); }, 0);
        });

        observer.observe(tbody, {
            childList: true,
            subtree: false,
            attributes: true,
            attributeFilter: ['style', 'hidden', 'class']
        });

        state.render = render;
        render(true);
        return state;
    }

    function createListPaginator(container) {
        if (!container || container.dataset.astarListPaginationReady === '1') return null;
        container.dataset.astarListPaginationReady = '1';

        const controls = createControls(container);
        const state = {
            currentPage: 1,
            prev: controls.querySelector('.astar-page-prev'),
            next: controls.querySelector('.astar-page-next'),
            indicator: controls.querySelector('.astar-page-indicator'),
            rendering: false,
            scrollTarget: container,
        };

        function getItems() {
            return Array.from(container.querySelectorAll('[data-astar-pagination-item]')).filter(function (item) {
                return item.closest('[data-astar-list-pagination]') === container && isFilterVisible(item);
            });
        }

        function updateGroups() {
            const groups = Array.from(container.querySelectorAll('[data-astar-pagination-group]')).filter(function (group) {
                return group.closest('[data-astar-list-pagination]') === container;
            });

            groups.forEach(function (group) {
                const visibleItems = Array.from(group.querySelectorAll('[data-astar-pagination-item]')).some(function (item) {
                    return !item.classList.contains('astar-pagination-hidden');
                });
                group.classList.toggle('astar-pagination-group-hidden', !visibleItems);
            });
        }

        function render(resetPage) {
            if (state.rendering) return;
            state.rendering = true;
            if (resetPage) state.currentPage = 1;

            const items = getItems();
            const totalPages = Math.max(1, Math.ceil(items.length / ROWS_PER_PAGE));
            state.currentPage = Math.min(Math.max(state.currentPage, 1), totalPages);

            const start = (state.currentPage - 1) * ROWS_PER_PAGE;
            const visibleSet = new Set(items.slice(start, start + ROWS_PER_PAGE));

            items.forEach(function (item) {
                item.classList.toggle('astar-pagination-hidden', !visibleSet.has(item));
            });

            updateGroups();
            state.indicator.textContent = state.currentPage + '/' + totalPages;
            state.prev.disabled = state.currentPage <= 1;
            state.next.disabled = state.currentPage >= totalPages;
            state.rendering = false;
        }

        bindNavigation(state, function () { return getItems().length; }, render);

        const observer = new MutationObserver(function (mutations) {
            const relevant = mutations.some(function (mutation) {
                return mutation.type === 'childList' ||
                    (mutation.type === 'attributes' && ['style', 'hidden'].includes(mutation.attributeName));
            });
            if (relevant && !state.rendering) window.setTimeout(function () { render(true); }, 0);
        });

        observer.observe(container, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'hidden']
        });

        state.render = render;
        render(true);
        return state;
    }

    function initializeAll() {
        document.querySelectorAll('table').forEach(function (table) {
            const paginator = createTablePaginator(table);
            if (paginator) paginators.push(paginator);
        });

        document.querySelectorAll('[data-astar-list-pagination]').forEach(function (container) {
            const paginator = createListPaginator(container);
            if (paginator) paginators.push(paginator);
        });
    }

    function refreshAll(resetPage) {
        paginators.forEach(function (paginator) {
            paginator.render(Boolean(resetPage));
        });
    }

    function scheduleRefresh() {
        window.setTimeout(function () { refreshAll(true); }, 0);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAll);
    } else {
        initializeAll();
    }

    document.addEventListener('input', function (event) {
        if (event.target.matches('input, select, textarea')) scheduleRefresh();
    });
    document.addEventListener('change', function (event) {
        if (event.target.matches('input, select, textarea')) scheduleRefresh();
    });

    window.ASTARTablePagination = {
        refresh: function (resetPage) { refreshAll(resetPage !== false); },
        initialize: initializeAll
    };
})();
</script>
