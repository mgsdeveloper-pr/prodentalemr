<style>
    .vt3-shell .uel2-structured .uel2-shell { border: 0; border-radius: 0; background: #fff; }
    .vt3-shell .uel2-structured .uel2-shell__inner { padding: 14px; gap: 14px; background: #fff; }
    .vt3-shell .uel2-structured .uel2-header { padding: 15px 18px; }
    .vt3-shell .uel2-structured .uel2-body { padding: 16px 18px 18px; }
    .vt3-shell .uel2-structured .uel2-structured-group { margin: 0; padding: 0; border: 0; border-radius: 0; }
    .vt3-shell .uel2-structured .uel2-structured-group + .uel2-structured-group { margin-top: 20px; padding-top: 16px; border-top: 1px solid #dce8e3; }
    .vt3-shell .uel2-structured .uel2-subsection__header { margin: 0 0 14px; padding: 0; background: transparent; border: 0; }
    .vt3-shell .uel2-structured .uel2-subsection__header h3 { font-size: 14px; font-weight: 900; line-height: 21px; letter-spacing: 0; color: var(--uel2-dark); }
    .vt3-shell .uel2-structured-fields { display: grid; gap: 0; min-width: 0; }
    .vt3-shell .uel2-structured-fields--details { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px 14px; }
    .vt3-shell .uel2-structured-fields--amounts { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 14px; }
    .vt3-shell .uel2-structured-field { min-width: 0; }
    .vt3-shell .uel2-structured-field--wide { grid-column: 1 / -1; }
    .vt3-shell .uel2-structured .uel2-managed-question { border: 0; border-radius: 0; padding: 12px 0; margin: 0; gap: 14px; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); }
    .vt3-shell .uel2-structured-fields--rows > .uel2-structured-field + .uel2-structured-field { border-top: 1px solid #dce8e3; }
    .vt3-shell .uel2-structured-fields--details .uel2-managed-question,
    .vt3-shell .uel2-structured-fields--amounts .uel2-managed-question,
    .vt3-shell .uel2-structured-field--wide .uel2-managed-question { grid-template-columns: minmax(0, 1fr); padding: 0; gap: 8px; }
    .vt3-shell .uel2-structured-fields--details .uel2-managed-question,
    .vt3-shell .uel2-structured-fields--amounts .uel2-managed-question { gap: 7px; }
    .vt3-shell .uel2-structured-fields--rows .uel2-structured-field--wide .uel2-managed-question { padding: 14px 0; }
    .vt3-shell .uel2-structured-fields--details .uel2-question-label,
    .vt3-shell .uel2-structured-fields--amounts .uel2-question-label { font-size: 11px; font-weight: 900; line-height: 16.5px; text-transform: uppercase; color: var(--uel2-muted); letter-spacing: 0; }
    .vt3-shell .uel2-structured .uel2-question-copy,
    .vt3-shell .uel2-structured .uel2-question-response { min-width: 0; overflow-wrap: anywhere; }
    .vt3-shell .uel2-structured .uel2-question-note { grid-column: 1 / -1; }
    .vt3-shell .uel2-structured .uel2-structured-group--benefits > .uel2-subsection__header { padding: 12px 14px; background: #eaf6f1; border-bottom: 1px solid #dce8e3; }
    .vt3-shell .uel2-structured .uel2-structured-group--benefits > .uel2-subsection__header h3 { font-size: var(--pwdl-font-size-body, 0.875rem); }
    .vt3-shell .uel2-structured .uel2-benefit-table { width: 100%; min-width: 0; }
    .vt3-shell .uel2-structured .uel2-benefit-table tbody tr { grid-template-columns: 76px minmax(0, 1fr) minmax(150px, .6fr) minmax(180px, .8fr); }
    .vt3-shell .uel2-structured .uel2-benefit-table td[data-label="Response Details"] { grid-column: 3 / -1; }
    .vt3-shell .uel2-structured .uel2-benefit-table td[data-label="Response Details"]:has(> .uel2-benefit-empty) { display: none; }
    .vt3-shell .uel2-structured .uel2-benefit-table td { min-width: 0; overflow-wrap: anywhere; }
    .vt3-shell .uel2-structured .uel2-benefit-table input { min-width: 0; width: 100%; }
    .vt3-shell .uel2-structured .uel2-question-response--paired { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; align-items: center; }
    .vt3-shell .uel2-structured .uel2-subsection__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .vt3-shell .uel2-structured .uel2-header h2 { margin: 0; }
    .vt3-shell .uel2-structured input, .vt3-shell .uel2-structured select { min-height: 38px; }
    @media (max-width: 1200px) {
        .vt3-shell .uel2-structured-fields--details { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 700px) {
        .verification-reference-workspace--edit { padding-right: 32px; }
        .vt3-integrated-header .vt3-compact-workbar { grid-template-columns: minmax(0, 1fr); gap: 10px; padding: 12px; }
        .vt3-integrated-header .vt3-compact-workbar__title-row { display: block; }
        .vt3-integrated-header .vt3-compact-workbar__context { display: grid; grid-template-columns: minmax(0, 1fr); margin-top: 8px; }
        .vt3-integrated-header .vt3-header-context-item { display: block; border: 0; padding: 2px 0; }
        .vt3-integrated-header .vt3-compact-workbar__actions { flex-wrap: wrap; justify-content: flex-start; }
        .vt3-shell .uel2-structured-fields--details,
        .vt3-shell .uel2-structured-fields--amounts,
        .vt3-shell .uel2-structured .uel2-managed-question { grid-template-columns: minmax(0, 1fr); }
        .vt3-shell .uel2-structured .uel2-body { padding: 12px; }
        .vt3-shell .uel2-structured .uel2-shell__inner { padding: 8px; }
        .vt3-shell .uel2-structured .uel2-benefit-table tbody tr { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .vt3-shell .uel2-structured .uel2-benefit-table td[data-label="Description"] { grid-column: 1 / -1; }
        .vt3-shell .uel2-structured .uel2-benefit-table td[data-label="%"] { grid-column: 1; grid-row: auto; }
        .vt3-shell .uel2-structured .uel2-benefit-table td[data-label="Frequency"] { grid-column: 2; grid-row: auto; }
        .vt3-shell .uel2-structured .uel2-benefit-table td[data-label="Response Details"] { grid-column: 1 / -1; }
    }
</style>
