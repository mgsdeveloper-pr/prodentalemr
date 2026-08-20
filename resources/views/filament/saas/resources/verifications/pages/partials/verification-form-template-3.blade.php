<div class="vt3-shell">
    @include('filament.saas.resources.verifications.pages.partials.verification-form-template-3-content')
</div>

<style>
    .vt3-shell {
        --vt3-ink: #0f172a;
        --vt3-deep: #0f5132;
        --vt3-brand: #0f766e;
        --vt3-soft: #eff8f3;
        --vt3-line: #d8e6df;
        --vt3-line-strong: #c7d9d0;
        --vt3-muted: #64748b;
        --vt3-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
        --vt3-context-offset: calc(var(--pwdl-shell-topbar, 72px) + 12px);
    }

    .vt3-shell .uel2-page {
        gap: 0;
    }

    .vt3-shell .uel2-shell {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: none;
        overflow: visible;
    }

    .vt3-shell .uel2-shell__inner {
        padding: 14px;
        background: #ffffff;
    }

    .vt3-shell .uel2-layout {
        display: grid;
        grid-template-columns: 284px minmax(0, 1fr);
        gap: 14px;
        align-items: start;
    }

    .vt3-shell .uel2-sidebar {
        position: sticky;
        top: var(--vt3-context-offset);
        align-self: start;
        max-height: calc(100dvh - var(--vt3-context-offset) - 12px);
        padding-top: 0;
        margin-top: 0;
    }

    .vt3-shell .uel2-sidebar-rail {
        max-height: inherit;
        display: flex;
        flex-direction: column;
        gap: 0;
        padding: 0;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: none;
        overflow-y: auto;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #edf2f7;
    }

    .vt3-shell .uel2-sidebar-rail__section:nth-child(1) {
        order: 1;
    }

    .vt3-shell .uel2-sidebar-rail__section:nth-child(2) {
        order: 2;
        padding-top: 8px;
        margin-top: 0;
    }

    .vt3-shell .uel2-sidebar-rail__section:nth-child(3) {
        order: 3;
    }

    .vt3-shell .uel2-sidebar-rail::-webkit-scrollbar {
        width: 8px;
    }

    .vt3-shell .uel2-sidebar-rail::-webkit-scrollbar-track {
        background: #edf2f7;
        border-radius: 999px;
    }

    .vt3-shell .uel2-sidebar-rail::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
    }

    .vt3-shell .uel2-sidebar-rail__section {
        padding: 15px 16px;
        margin-bottom: 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .vt3-shell .uel2-sidebar-rail__section:last-child {
        border-bottom: 0;
    }

    .vt3-shell .uel2-sidebar-rail__title {
        margin-top: 0;
        margin-bottom: 6px;
    }

    .vt3-shell .uel2-sidebar-rail__section:nth-child(2) .uel2-sidebar-rail__title {
        margin-top: 0;
        margin-bottom: 4px;
    }

    .vt3-shell .uel2-sidebar-rail__title h2,
    .vt3-shell .uel2-sidebar-rail__title h3 {
        font-size: 14px;
        line-height: 1.2;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--vt3-deep);
    }

    .vt3-shell .uel2-sidebar-rail__copy {
        font-size: 12px;
        line-height: 1.5;
        color: #6b7f76;
    }

    .vt3-shell .uel2-progress-card,
    .vt3-shell .uel2-quick-reference,
    .vt3-shell .uel2-sidebar-block {
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .vt3-shell .uel2-quick-reference__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 12px;
    }

    .vt3-shell .uel2-quick-reference__label,
    .vt3-shell .uel2-sidebar-block__label {
        color: #738377;
    }

    .vt3-shell .uel2-progress-list {
        gap: 6px;
        margin-top: 10px;
    }

    .vt3-shell .uel2-progress-item {
        padding: 9px 10px;
        border-radius: 14px;
        border-color: #dbe7e2;
        background: #fbfdfc;
    }

    .vt3-shell .uel2-progress-card {
        padding: 12px 14px;
        border: 1px solid #dce8e2;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f9fcfa 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .vt3-shell .uel2-progress-total {
        margin-top: 8px;
        font-size: 11px;
    }

    .vt3-shell .uel2-progress-item__meta {
        gap: 8px;
    }

    .vt3-shell .uel2-progress-item__label {
        font-size: 12px;
    }

    .vt3-shell .uel2-progress-item__count {
        font-size: 11px;
    }

    .vt3-shell .uel2-content {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    .vt3-shell .uel2-section {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: none;
        overflow: hidden;
    }

    .vt3-shell .uel2-content > .uel2-section:nth-child(-n+2) {
        border-color: #c9ddd4;
    }

    .vt3-shell .uel2-header {
        padding: 15px 18px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .vt3-shell .uel2-header h2,
    .vt3-shell .uel2-header h3 {
        font-size: 15px;
        line-height: 1.2;
        color: var(--vt3-deep);
    }

    .vt3-shell .uel2-header p {
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.55;
        color: #6b7f76;
    }

    .vt3-shell .uel2-pill {
        border-color: #d8ece2;
        background: #eef8f2;
        color: #0f766e;
    }

    .vt3-shell .uel2-body {
        padding: 16px 18px 18px;
    }

    .vt3-shell .uel2-table {
        border-radius: 12px;
        overflow: hidden;
    }

    .vt3-shell .uel2-table thead th {
        background: #f8fafc;
        color: #5d7368;
    }

    .vt3-shell .uel2-table tbody td {
        vertical-align: top;
    }

    .vt3-shell .uel2-table input,
    .vt3-shell .uel2-table select,
    .vt3-shell .uel2-table textarea,
    .vt3-shell .uel2-grid input,
    .vt3-shell .uel2-grid select,
    .vt3-shell .uel2-grid textarea {
        min-height: 40px;
        border-radius: 12px;
    }

    .vt3-shell .uel2-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 8px;
    }

    .vt3-shell .uel2-actions > button,
    .vt3-shell .uel2-actions > a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 0;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        box-shadow: none;
        transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease, transform 140ms ease;
    }

    .vt3-shell .uel2-actions > button:hover,
    .vt3-shell .uel2-actions > a:hover {
        transform: translateY(-1px);
    }

    .vt3-shell .uel2-managed-question {
        border-radius: 12px;
        border-color: #e2e8f0;
        background: #ffffff;
    }

    .vt3-shell .uel2-field label {
        color: #61776d;
    }

    .vt3-shell .uel2-insurance-groups,
    .vt3-shell .uel2-grid {
        gap: 14px;
    }

    .vt3-shell .uel2-subsection {
        border-radius: 12px;
        border-color: #e2e8f0;
        background: #ffffff;
        box-shadow: none;
    }

    .vt3-shell .uel2-subsection__header {
        padding-bottom: 12px;
        margin-bottom: 12px;
        border-bottom: 1px solid #e8f0ec;
    }

    .vt3-shell .uel2-subsection__header h3 {
        font-size: 14px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--vt3-deep);
    }

    .vt3-shell .uel2-header,
    .vt3-shell .uel2-subsection__header,
    .vt3-shell .uel2-table thead th {
        scroll-margin-top: 118px;
    }

    .vt3-shell .uel2-quick-reference__item {
        padding: 9px 0;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        border-radius: 0;
        background: transparent;
    }

    .vt3-shell .uel2-quick-reference__item:last-child {
        border-bottom: 0;
    }

    .vt3-shell .uel2-quick-reference__value,
    .vt3-shell .uel2-sidebar-block__value {
        font-size: 13px;
        line-height: 1.45;
    }

    .vt3-shell .uel2-sidebar-block {
        padding: 12px 0;
        border-bottom: 1px solid #edf3f0;
    }

    .vt3-shell .uel2-sidebar-block:last-child {
        border-bottom: 0;
    }

    .vt3-shell .uel2-sidebar-block__title {
        position: sticky;
        top: 0;
        z-index: 1;
        margin: 0 -2px 10px;
        padding: 0 2px 8px;
        background: linear-gradient(180deg, rgba(247, 251, 248, 0.98) 0%, rgba(247, 251, 248, 0.94) 100%);
        color: #0f766e;
    }

    .vt3-shell .uel2-sidebar-block__rows {
        gap: 9px;
    }

    .vt3-shell .uel2-sidebar-block__row {
        position: relative;
        gap: 1px;
        padding-left: 18px;
    }

    .vt3-shell .uel2-sidebar-block__row::before {
        content: '';
        position: absolute;
        left: 0;
        top: 7px;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #c7d7cf;
        box-shadow: 0 0 0 3px #f2f7f4;
    }

    .vt3-shell .uel2-sidebar-block__value {
        color: #18493b;
    }

    .vt3-shell .uel2-sidebar-rail__section:has(.uel2-progress-list) .uel2-sidebar-rail__title {
        margin-bottom: 10px;
    }

    .vt3-shell .uel2-sidebar-rail__section:has(.uel2-quick-reference) .uel2-sidebar-rail__title {
        margin-bottom: 10px;
    }

    @media (max-width: 1180px) {
        .vt3-shell .uel2-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .vt3-shell .uel2-sidebar {
            position: static;
            height: auto;
            max-height: none;
        }

        .vt3-shell .uel2-sidebar-rail {
            max-height: none;
            overflow-x: visible;
            overflow-y: visible;
            scrollbar-gutter: auto;
        }

        .vt3-shell .uel2-content {
            padding: 12px;
        }

        .vt3-shell .uel2-quick-reference__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .vt3-shell .uel2-quick-reference__grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<script>
    (() => {
        if (window.__vt3WorksheetGridInit) {
            return;
        }

        window.__vt3WorksheetGridInit = true;

        document.addEventListener('keydown', (event) => {
            const target = event.target;

            if (!target || !(target instanceof HTMLElement)) {
                return;
            }

            if (!target.closest('.vt3-shell') || !target.closest('.uel2-table')) {
                return;
            }

            if (!['Enter', 'ArrowDown', 'ArrowUp'].includes(event.key)) {
                return;
            }

            if (target.tagName === 'TEXTAREA') {
                return;
            }

            const currentCell = target.closest('td');
            const currentRow = target.closest('tr');
            const table = target.closest('table');

            if (!currentCell || !currentRow || !table) {
                return;
            }

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const rowIndex = rows.indexOf(currentRow);
            const cells = Array.from(currentRow.querySelectorAll('td'));
            const cellIndex = cells.indexOf(currentCell);

            if (rowIndex === -1 || cellIndex === -1) {
                return;
            }

            const direction = event.key === 'ArrowUp' || (event.key === 'Enter' && event.shiftKey) ? -1 : 1;

            for (let nextRowIndex = rowIndex + direction; nextRowIndex >= 0 && nextRowIndex < rows.length; nextRowIndex += direction) {
                const nextRowCells = Array.from(rows[nextRowIndex].querySelectorAll('td'));
                const nextCell = nextRowCells[cellIndex];

                if (!nextCell) {
                    continue;
                }

                const nextFocusable = nextCell.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])');

                if (!nextFocusable) {
                    continue;
                }

                event.preventDefault();
                nextFocusable.focus();

                if (typeof nextFocusable.select === 'function' && nextFocusable.tagName !== 'SELECT') {
                    nextFocusable.select();
                }

                break;
            }
        });
    })();
</script>
