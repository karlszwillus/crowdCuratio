/*
 * crowdCuratio — DataTable-Manager
 *
 * Vanilla-JS-Ersatz für jquery.dataTables. Bietet die im Bestand
 * verwendeten Features:
 *
 *   - Sortierung per Klick auf Spalten-Header (asc/desc/none)
 *   - Volltext-Suche per Input über der Tabelle
 *   - Pagination mit Page-Size-Auswahl
 *   - language.search/info/paginate für deutsche UI-Strings
 *
 * Konsumiert die gleichen Optionen wie DataTables, sodass die drei
 * bestehenden Init-Aufrufe in projects/index, users/index und
 * contents/comment unverändert weiterlaufen:
 *
 *   $('#xxxList').DataTable({
 *     paging: true,
 *     info: true,
 *     language: { search, info, paginate, lengthMenu },
 *   });
 *
 * Unsupported Features (in unserem Bestand nicht genutzt): ajax-Source,
 * columnDefs, scrollX, fixed-Headers. Lokales Daten-Set aus dem
 * bestehenden `<tbody>` reicht.
 */

const DEFAULTS = {
    paging: true,
    info: true,
    pageLength: 10,
    pageLengths: [10, 25, 50, 100],
    language: {
        search: 'Search:',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: 'No entries to show',
        lengthMenu: 'Show _MENU_ entries',
        emptyTable: 'No data available',
        paginate: {
            first: 'First',
            previous: 'Previous',
            next: 'Next',
            last: 'Last',
        },
    },
};

function mergeLanguage(custom) {
    return {
        ...DEFAULTS.language,
        ...(custom || {}),
        paginate: {
            ...DEFAULTS.language.paginate,
            ...((custom || {}).paginate || {}),
        },
    };
}

function attach(table, options) {
    if (table.__datatable) return;
    const opts = {
        paging: true,
        info: true,
        pageLength: DEFAULTS.pageLength,
        pageLengths: DEFAULTS.pageLengths,
        ...(options || {}),
        language: mergeLanguage(options && options.language),
    };

    const headerCells = Array.from(table.tHead?.rows?.[0]?.cells ?? []);
    const orderable = headerCells.map((th) => th.getAttribute('data-orderable') !== 'false');

    let allRows = Array.from(table.tBodies[0]?.rows ?? []).map((tr) => tr.cloneNode(true));
    let filteredRows = allRows.slice();
    let pageSize = opts.pageLength;
    let currentPage = 1;
    let sortColumn = null;
    let sortDirection = null; // 'asc' | 'desc' | null
    let searchTerm = '';

    // ---------- Controls oberhalb (Search + Length) ----------
    const wrapper = document.createElement('div');
    wrapper.className = 'datatable-wrapper';
    table.parentNode.insertBefore(wrapper, table);

    const topBar = document.createElement('div');
    topBar.className = 'datatable-topbar flex flex-wrap items-center justify-between gap-3 py-2';

    const lengthBox = document.createElement('label');
    lengthBox.className = 'datatable-length text-caption text-ink-700 flex items-center gap-2';
    const lengthSelect = document.createElement('select');
    lengthSelect.className = 'form-control text-caption';
    lengthSelect.style.width = 'auto';
    opts.pageLengths.forEach((n) => {
        const opt = document.createElement('option');
        opt.value = String(n);
        opt.textContent = String(n);
        if (n === opts.pageLength) opt.selected = true;
        lengthSelect.appendChild(opt);
    });
    const lengthLabel = opts.language.lengthMenu.replace('_MENU_', '');
    if (lengthLabel.includes('_MENU_')) {
        // already handled
    }
    lengthBox.appendChild(document.createTextNode(lengthLabel.trim()));
    lengthBox.appendChild(lengthSelect);
    topBar.appendChild(lengthBox);

    const searchBox = document.createElement('label');
    searchBox.className = 'datatable-search text-caption text-ink-700 flex items-center gap-2';
    searchBox.appendChild(document.createTextNode(opts.language.search));
    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.className = 'form-control text-caption';
    searchInput.style.width = '14rem';
    searchInput.setAttribute('aria-label', opts.language.search);
    searchBox.appendChild(searchInput);
    topBar.appendChild(searchBox);

    wrapper.appendChild(topBar);
    wrapper.appendChild(table);

    // ---------- Footer (Info + Pagination) ----------
    const footerBar = document.createElement('div');
    footerBar.className = 'datatable-footer flex flex-wrap items-center justify-between gap-3 py-2 text-caption text-ink-700';
    const infoNode = document.createElement('div');
    infoNode.className = 'datatable-info';
    const pagerNode = document.createElement('nav');
    pagerNode.className = 'datatable-pagination flex items-center gap-1';
    pagerNode.setAttribute('aria-label', 'Pagination');
    footerBar.appendChild(infoNode);
    footerBar.appendChild(pagerNode);
    wrapper.appendChild(footerBar);

    // ---------- Sortier-Icons in Header ----------
    headerCells.forEach((th, idx) => {
        if (!orderable[idx]) return;
        th.classList.add('cursor-pointer', 'select-none');
        th.style.cursor = 'pointer';
        th.setAttribute('role', 'button');
        const arrow = document.createElement('span');
        arrow.className = 'datatable-sort-arrow opacity-50 ml-1';
        arrow.textContent = '↕';
        th.appendChild(arrow);
        th.addEventListener('click', () => {
            if (sortColumn === idx) {
                sortDirection = sortDirection === 'asc' ? 'desc' : (sortDirection === 'desc' ? null : 'asc');
                if (sortDirection === null) sortColumn = null;
            } else {
                sortColumn = idx;
                sortDirection = 'asc';
            }
            updateSortIndicators();
            render();
        });
    });

    function updateSortIndicators() {
        headerCells.forEach((th, idx) => {
            const arrow = th.querySelector('.datatable-sort-arrow');
            if (!arrow) return;
            if (sortColumn === idx && sortDirection === 'asc') arrow.textContent = '↑';
            else if (sortColumn === idx && sortDirection === 'desc') arrow.textContent = '↓';
            else arrow.textContent = '↕';
        });
    }

    function applyFilter() {
        const q = searchTerm.trim().toLowerCase();
        if (q === '') {
            filteredRows = allRows.slice();
        } else {
            filteredRows = allRows.filter((tr) => tr.textContent.toLowerCase().includes(q));
        }
        currentPage = 1;
    }

    function applySort() {
        if (sortColumn === null || sortDirection === null) return;
        filteredRows.sort((a, b) => {
            const av = (a.cells[sortColumn]?.textContent || '').trim();
            const bv = (b.cells[sortColumn]?.textContent || '').trim();
            const an = parseFloat(av);
            const bn = parseFloat(bv);
            const bothNum = !isNaN(an) && !isNaN(bn) && /^[-\d.,\s]+$/.test(av) && /^[-\d.,\s]+$/.test(bv);
            const cmp = bothNum ? (an - bn) : av.localeCompare(bv, undefined, { numeric: true });
            return sortDirection === 'asc' ? cmp : -cmp;
        });
    }

    function render() {
        applyFilter();
        applySort();

        const total = allRows.length;
        const filtered = filteredRows.length;
        const start = filtered === 0 ? 0 : (currentPage - 1) * pageSize + 1;
        const end = Math.min(currentPage * pageSize, filtered);

        const tbody = table.tBodies[0];
        if (tbody) {
            tbody.innerHTML = '';
            if (opts.paging) {
                filteredRows.slice((currentPage - 1) * pageSize, currentPage * pageSize)
                    .forEach((tr) => tbody.appendChild(tr));
            } else {
                filteredRows.forEach((tr) => tbody.appendChild(tr));
            }
        }

        if (opts.info) {
            if (filtered === 0) {
                infoNode.textContent = opts.language.infoEmpty;
            } else {
                infoNode.textContent = opts.language.info
                    .replace('_START_', String(start))
                    .replace('_END_', String(end))
                    .replace('_TOTAL_', String(filtered))
                    .replace('_PAGE_', String(currentPage))
                    .replace('_PAGES_', String(Math.max(1, Math.ceil(filtered / pageSize))));
            }
        }

        renderPager(filtered);
    }

    function renderPager(filtered) {
        pagerNode.innerHTML = '';
        if (!opts.paging) return;

        const totalPages = Math.max(1, Math.ceil(filtered / pageSize));

        const mkBtn = (label, page, disabled = false, active = false) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-default btn-sm' + (active ? ' active' : '');
            btn.textContent = label;
            if (disabled) {
                btn.setAttribute('disabled', '');
                btn.style.opacity = '0.5';
            } else {
                btn.addEventListener('click', () => {
                    currentPage = page;
                    render();
                });
            }
            return btn;
        };

        pagerNode.appendChild(mkBtn(opts.language.paginate.previous, currentPage - 1, currentPage === 1));

        const range = pageRange(currentPage, totalPages, 5);
        range.forEach((p) => {
            if (p === '…') {
                const sep = document.createElement('span');
                sep.textContent = '…';
                sep.className = 'px-2 text-ink-500';
                pagerNode.appendChild(sep);
            } else {
                pagerNode.appendChild(mkBtn(String(p), p, false, p === currentPage));
            }
        });

        pagerNode.appendChild(mkBtn(opts.language.paginate.next, currentPage + 1, currentPage === totalPages));
    }

    function pageRange(current, total, window) {
        if (total <= window + 2) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        const half = Math.floor(window / 2);
        let from = Math.max(2, current - half);
        let to = Math.min(total - 1, current + half);
        if (current - half < 2) to = Math.min(total - 1, window);
        if (current + half > total - 1) from = Math.max(2, total - window + 1);
        const out = [1];
        if (from > 2) out.push('…');
        for (let i = from; i <= to; i++) out.push(i);
        if (to < total - 1) out.push('…');
        out.push(total);
        return out;
    }

    searchInput.addEventListener('input', () => {
        searchTerm = searchInput.value;
        render();
    });

    lengthSelect.addEventListener('change', () => {
        pageSize = Number(lengthSelect.value) || DEFAULTS.pageLength;
        currentPage = 1;
        render();
    });

    table.__datatable = {
        render,
        destroy: () => {
            wrapper.parentNode.insertBefore(table, wrapper);
            wrapper.remove();
            delete table.__datatable;
        },
    };

    render();
}

function installJQueryShim() {
    if (!window.jQuery) return;
    if (window.jQuery.fn.DataTable && window.jQuery.fn.DataTable.__crowdCuratio) return;

    window.jQuery.fn.DataTable = function (options) {
        this.each(function () { attach(this, options); });
        return this;
    };
    window.jQuery.fn.DataTable.__crowdCuratio = true;

    // Lowercase-Alias, manche Bestände nutzen $('xxx').dataTable(...)
    window.jQuery.fn.dataTable = window.jQuery.fn.DataTable;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installJQueryShim);
} else {
    installJQueryShim();
}

window.crowdCuratioDataTable = { attach };
