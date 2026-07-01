<div class="vt3-shell">
    @include('filament.saas.resources.verifications.pages.partials.verification-form-template-2')
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
    }

    .vt3-shell .uel2-page {
        gap: 0;
    }

    .vt3-shell .uel2-shell {
        border: 1px solid var(--vt3-line);
        border-radius: 34px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(246, 251, 248, 1) 100%);
        box-shadow: var(--vt3-shadow);
        overflow: hidden;
    }

    .vt3-shell .uel2-shell__inner {
        padding: 12px 12px 16px;
        background: linear-gradient(180deg, rgba(242, 248, 245, 0.95) 0%, rgba(249, 252, 251, 0.98) 100%);
    }

    .vt3-shell .uel2-layout {
        display: grid;
        grid-template-columns: 292px minmax(0, 1fr);
        gap: 12px;
        align-items: start;
    }

    .vt3-shell .uel2-sidebar {
        position: sticky;
        top: 0;
        align-self: start;
        padding-top: 0;
        margin-top: 0;
    }

    .vt3-shell .uel2-sidebar-rail {
        max-height: max-content;
        display: flex;
        flex-direction: column;
        gap: 0;
        padding: 4px 8px 10px 10px;
        border: 1px solid var(--vt3-line);
        border-radius: 28px;
        background: linear-gradient(180deg, #ffffff 0%, #f7fbf8 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        overflow-y: visible;
        overflow-x: hidden;
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
        padding: 10px 8px 14px;
        margin-bottom: 0;
        border-bottom: 1px solid #e5efea;
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
        letter-spacing: 0.08em;
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
        padding: 12px;
        border: 1px solid var(--vt3-line);
        border-radius: 28px;
        background: linear-gradient(180deg, rgba(253, 255, 254, 1) 0%, rgba(248, 252, 250, 1) 100%);
    }

    .vt3-shell .uel2-section {
        border: 1px solid #dfe9e4;
        border-radius: 22px;
        background: #ffffff;
        box-shadow: none;
        overflow: hidden;
    }

    .vt3-shell .uel2-content > .uel2-section:nth-child(-n+2) {
        border-color: #c9ddd4;
    }

    .vt3-shell .uel2-header {
        padding: 14px 18px 12px;
        border-bottom: 1px solid #e7f0eb;
        background: linear-gradient(180deg, rgba(250, 253, 251, 1) 0%, rgba(244, 249, 246, 1) 100%);
    }

    .vt3-shell .uel2-header h2,
    .vt3-shell .uel2-header h3 {
        font-size: 16px;
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
        padding: 14px 18px 18px;
    }

    .vt3-shell .uel2-table {
        border-radius: 16px;
        overflow: hidden;
    }

    .vt3-shell .uel2-table thead th {
        background: #f4faf6;
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

    .vt3-shell .uel2-managed-question {
        border-radius: 16px;
        border-color: #dbe7e2;
        background: #fbfdfc;
    }

    .vt3-shell .uel2-field label {
        color: #61776d;
    }

    .vt3-shell .uel2-insurance-groups,
    .vt3-shell .uel2-grid {
        gap: 14px;
    }

    .vt3-shell .uel2-subsection {
        border-radius: 18px;
        border-color: #dfe9e4;
        background: #fcfefd;
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
        padding: 10px 10px 9px;
        border: 1px solid #e3ede8;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdfc 100%);
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
        }

        .vt3-shell .uel2-content {
            padding: 12px;
        }

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
