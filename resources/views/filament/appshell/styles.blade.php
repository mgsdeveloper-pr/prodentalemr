<style>
    :root {
        --pwdl-brand-teal: #0f766e;
        --pwdl-brand-teal-hover: #115e59;
        --pwdl-brand-teal-active: #134e4a;
        --pwdl-brand-teal-soft: rgba(15, 118, 110, 0.08);
        --pwdl-brand-teal-ring: rgba(15, 118, 110, 0.28);
        --pwdl-brand-primary: var(--pwdl-brand-teal);
        --pwdl-brand-primary-hover: var(--pwdl-brand-teal-hover);
        --pwdl-brand-primary-active: var(--pwdl-brand-teal-active);
        --pwdl-brand-primary-soft: var(--pwdl-brand-teal-soft);
        --pwdl-brand-primary-ring: var(--pwdl-brand-teal-ring);
        --pwdl-neutral-white: #ffffff;
        --pwdl-neutral-soft-gray: #f8fafc;
        --pwdl-neutral-medium-gray: #64748b;
        --pwdl-neutral-dark-gray: #0f172a;
        --pwdl-surface-background: #f8fafc;
        --pwdl-surface-card: #ffffff;
        --pwdl-surface-muted: #f1f5f9;
        --pwdl-border-default: #dbe4ee;
        --pwdl-border-subtle: #e2e8f0;
        --pwdl-text-primary: #0f172a;
        --pwdl-text-secondary: #475569;
        --pwdl-text-muted: #64748b;
        --pwdl-text-placeholder: #94a3b8;
        --pwdl-status-success: #059669;
        --pwdl-status-success-soft: #ecfdf5;
        --pwdl-status-warning: #d97706;
        --pwdl-status-warning-soft: #fffbeb;
        --pwdl-status-danger: #e11d48;
        --pwdl-status-danger-soft: #fff1f2;
        --pwdl-status-info: #0284c7;
        --pwdl-status-info-soft: #f0f9ff;
        --pwdl-radius-md: 8px;
        --pwdl-radius-lg: 12px;
        --pwdl-radius-xl: 14px;
        --pwdl-space-sm: 0.5rem;
        --pwdl-space-md: 0.75rem;
        --pwdl-space-lg: 1rem;
        --pwdl-space-xl: 1.5rem;
        --pwdl-font-size-workspace-title: 1.5rem;
        --pwdl-font-size-body: 0.875rem;
        --pwdl-font-size-caption: 0.75rem;
        --pwdl-shadow-card: 0 1px 2px rgba(15, 23, 42, 0.06);
        --pwdl-shadow-floating: 0 16px 40px rgba(15, 23, 42, 0.18);
        --pwdl-layout-left: 300px;
        --pwdl-layout-right: 320px;
        --pwdl-layout-gap: var(--pwdl-space-xl);
        --pwdl-shell-topbar: 72px;
        --pwdl-shell-sidebar: #ffffff;
        --pwdl-shell-sidebar-hover: #f1f5f9;
        --pwdl-shell-sidebar-border: #e2e8f0;
        --pwdl-shell-sidebar-text: #334155;
        --pwdl-shell-sidebar-muted: #64748b;
        --pwdl-color-ratio-neutral: 90%;
        --pwdl-color-ratio-brand: 8%;
        --pwdl-color-ratio-semantic: 2%;
        --pwdl-control-radius: 10px;
        --pd-primary: var(--pwdl-brand-primary);
        --pd-bg: var(--pwdl-surface-background);
        --pd-card: var(--pwdl-surface-card);
        --pd-border: var(--pwdl-border-default);
        --pd-muted: var(--pwdl-text-muted);
        --pd-text: var(--pwdl-text-primary);
        --pd-shadow: var(--pwdl-shadow-card);
        --pd-sidebar-expanded: 260px;
        --pd-sidebar-collapsed: 72px;
    }

    .fi-body {
        background: var(--pd-bg);
        color: var(--pd-text);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .fi-topbar {
        min-height: var(--pwdl-shell-topbar);
        border-bottom: 1px solid #cbd5e1;
        background: var(--pwdl-surface-card);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        backdrop-filter: none;
    }

    .fi-topbar:has(.pd-appshell-global-header) {
        gap: var(--pwdl-space-sm);
    }

    .fi-topbar:has(.pd-appshell-global-header) .fi-topbar-start,
    .fi-topbar:has(.pd-appshell-global-header) .fi-topbar-end {
        display: none;
    }

    .fi-sidebar {
        width: var(--pd-sidebar-expanded);
        border-right: 1px solid var(--pwdl-shell-sidebar-border);
        background: var(--pwdl-shell-sidebar);
        color: var(--pwdl-shell-sidebar-text);
    }

    .fi-sidebar.fi-sidebar-collapsed {
        width: var(--pd-sidebar-collapsed);
    }

    .fi-sidebar-nav-groups {
        gap: 0.35rem;
    }

    .fi-sidebar-item a,
    .fi-sidebar-group-button {
        border-radius: var(--pwdl-control-radius);
        color: var(--pwdl-shell-sidebar-text);
    }

    .fi-sidebar-item a:hover,
    .fi-sidebar-group-button:hover {
        background: var(--pwdl-shell-sidebar-hover);
        color: var(--pwdl-neutral-dark-gray);
    }

    .fi-sidebar-item-active a {
        border-left: 3px solid var(--pwdl-brand-primary);
        background: var(--pwdl-brand-primary-soft);
        color: var(--pwdl-brand-primary);
    }

    .fi-sidebar-group-label,
    .fi-sidebar-item-label,
    .fi-sidebar-item-icon,
    .fi-sidebar-group-icon,
    .fi-sidebar-collapse-button {
        color: inherit;
    }

    .fi-main {
        padding-block-end: 0;
    }

    .fi-header {
        border: 0;
        border-bottom: 1px solid var(--pwdl-border-subtle);
        border-radius: 0;
        background: var(--pwdl-surface-card);
        box-shadow: var(--pd-shadow);
        padding: 1rem 1.5rem;
    }

    .pd-appshell-global-header {
        display: grid;
        grid-template-columns: max-content max-content minmax(18rem, 1fr) max-content;
        align-items: center;
        gap: var(--pwdl-space-lg);
        width: 100%;
        flex: 1 1 auto;
        min-height: 3rem;
        padding-inline: var(--pwdl-space-sm) var(--pwdl-space-lg);
        color: var(--pd-muted);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0;
        white-space: nowrap;
    }

    .pd-appshell-global-header__identity,
    .pd-appshell-global-header__brand,
    .pd-appshell-global-header__search,
    .pd-appshell-global-header__utilities,
    .pd-appshell-workspace-switcher {
        display: inline-flex;
        align-items: center;
        gap: var(--pwdl-space-sm);
    }

    .pd-appshell-global-header__brand {
        min-width: 10rem;
        color: var(--pwdl-text-primary);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .pd-appshell-global-header__logo {
        display: inline-flex;
        width: 2.25rem;
        height: 2.25rem;
        align-items: center;
        justify-content: center;
        border-radius: var(--pwdl-radius-lg);
        background: var(--pwdl-brand-primary);
        color: var(--pwdl-text-inverse, #ffffff);
        font-size: 0.78rem;
        font-weight: 900;
    }

    .pd-appshell-global-header__brand-name {
        color: var(--pwdl-text-primary);
        font-weight: 850;
    }

    .pd-appshell-workspace-switcher {
        position: relative;
        max-width: 16rem;
    }

    .pd-appshell-workspace-switcher__summary {
        display: inline-flex;
        min-height: 2.25rem;
        cursor: pointer;
        list-style: none;
        align-items: center;
        gap: 0.45rem;
        border: 1px solid var(--pwdl-border-subtle);
        border-radius: var(--pwdl-control-radius);
        background: var(--pwdl-surface-card);
        padding-inline: var(--pwdl-space-md);
        color: var(--pwdl-text-secondary);
        font-weight: 800;
    }

    .pd-appshell-workspace-switcher__summary::-webkit-details-marker {
        display: none;
    }

    .pd-appshell-workspace-switcher__menu {
        position: absolute;
        top: calc(100% + 0.45rem);
        left: 0;
        z-index: 60;
        display: grid;
        min-width: 12rem;
        gap: 0.2rem;
        border: 1px solid var(--pwdl-border-subtle);
        border-radius: var(--pwdl-radius-lg);
        background: var(--pwdl-surface-card);
        box-shadow: var(--pwdl-shadow-floating);
        padding: 0.4rem;
    }

    .pd-appshell-workspace-switcher:not([open]) .pd-appshell-workspace-switcher__menu {
        display: none;
    }

    .pd-appshell-workspace-switcher__link,
    .pd-appshell-workspace-switcher__future,
    .pd-appshell-slot {
        display: inline-flex;
        align-items: center;
        min-height: 2.25rem;
        border: 1px solid transparent;
        border-radius: var(--pwdl-control-radius);
        padding-inline: 0.5rem;
        color: var(--pd-muted);
        text-decoration: none;
    }

    .pd-appshell-workspace-switcher__link:focus-visible {
        outline: 2px solid rgba(15, 118, 110, 0.45);
        outline-offset: 2px;
    }

    .pd-appshell-workspace-switcher__link--active {
        border-color: color-mix(in srgb, var(--pwdl-brand-primary) 18%, transparent);
        background: var(--pwdl-brand-primary-soft);
        color: var(--pwdl-brand-primary);
    }

    .pd-appshell-workspace-switcher__future {
        opacity: 0.52;
    }

    .pd-appshell-global-header__search {
        justify-content: center;
        min-width: 0;
    }

    .pd-appshell-global-header__utilities {
        justify-content: flex-end;
        min-width: 0;
    }

    .pd-appshell-slot {
        border-color: var(--pwdl-border-subtle);
        background: var(--pwdl-surface-card);
        padding-inline: 0.7rem;
        color: var(--pwdl-text-secondary);
        font-size: 0.72rem;
    }

    .pd-appshell-slot--search {
        flex: 1;
        width: min(100%, 34rem);
        max-width: 34rem;
        min-width: 0;
        border-color: var(--pwdl-border-subtle);
        background: var(--pwdl-surface-background);
        color: var(--pwdl-text-placeholder);
        font-weight: 600;
    }

    .pd-appshell-slot--kbd {
        min-height: 1.65rem;
        border-radius: 7px;
        background: #ffffff;
        color: var(--pwdl-text-muted);
        line-height: 1;
    }

    .pd-appshell-user-menu {
        position: relative;
    }

    .pd-appshell-user-menu__summary {
        display: inline-flex;
        cursor: pointer;
        list-style: none;
        align-items: center;
        justify-content: center;
        border-radius: var(--pwdl-radius-full, 999px);
    }

    .pd-appshell-user-menu__summary::-webkit-details-marker {
        display: none;
    }

    .pd-appshell-user-menu__avatar {
        display: inline-flex;
        width: 2.25rem;
        height: 2.25rem;
        align-items: center;
        justify-content: center;
        border: 1px solid color-mix(in srgb, var(--pwdl-brand-primary) 24%, var(--pwdl-border-subtle));
        border-radius: var(--pwdl-radius-full, 999px);
        background: var(--pwdl-brand-primary-soft);
        color: var(--pwdl-brand-primary);
        font-size: 0.78rem;
        font-weight: 850;
    }

    .pd-appshell-user-menu__panel {
        position: absolute;
        top: calc(100% + 0.45rem);
        right: 0;
        z-index: 60;
        display: grid;
        min-width: 13rem;
        gap: var(--pwdl-space-sm);
        border: 1px solid var(--pwdl-border-subtle);
        border-radius: var(--pwdl-radius-lg);
        background: var(--pwdl-surface-card);
        box-shadow: var(--pwdl-shadow-floating);
        padding: var(--pwdl-space-md);
    }

    .pd-appshell-user-menu:not([open]) .pd-appshell-user-menu__panel {
        display: none;
    }

    .pd-appshell-user-menu__identity {
        display: grid;
        gap: 0.15rem;
        padding-bottom: var(--pwdl-space-sm);
        border-bottom: 1px solid var(--pwdl-border-subtle);
    }

    .pd-appshell-user-menu__name {
        color: var(--pwdl-text-primary);
        font-size: 0.82rem;
        font-weight: 850;
    }

    .pd-appshell-user-menu__email {
        overflow: hidden;
        color: var(--pwdl-text-muted);
        font-size: 0.72rem;
        font-weight: 650;
        text-overflow: ellipsis;
    }

    .pd-appshell-user-menu__link {
        display: inline-flex;
        width: 100%;
        min-height: 2rem;
        align-items: center;
        border-radius: var(--pwdl-control-radius);
        padding-inline: var(--pwdl-space-sm);
        color: var(--pwdl-text-secondary);
        text-decoration: none;
    }

    .pd-appshell-user-menu__button {
        border: 0;
        background: transparent;
        cursor: pointer;
        font: inherit;
        text-align: left;
    }

    .pd-appshell-user-menu__link:hover {
        background: var(--pwdl-surface-background);
        color: var(--pwdl-text-primary);
    }

    .pwdl-workspace {
        background: var(--pwdl-surface-background);
        color: var(--pwdl-text-primary);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .pwdl-card {
        border-color: var(--pwdl-border-subtle);
        border-radius: var(--pwdl-radius-xl);
        background: var(--pwdl-surface-card);
        box-shadow: var(--pwdl-shadow-card);
    }

    .pwdl-empty-state {
        min-height: 140px;
        border-color: var(--pwdl-border-strong, #cbd5e1);
        background: var(--pwdl-surface-background);
    }

    .pds-badge--brand {
        border-color: color-mix(in srgb, var(--pwdl-brand-primary) 20%, white);
        background: var(--pwdl-brand-primary-soft);
        color: var(--pwdl-brand-primary);
    }

    .pds-status-pill--success {
        border-color: color-mix(in srgb, var(--pwdl-status-success) 20%, white);
        background: var(--pwdl-status-success-soft);
        color: var(--pwdl-status-success);
    }

    .pds-button--primary-token {
        border-color: var(--pwdl-brand-primary);
        background: var(--pwdl-brand-primary);
    }

    .pds-button--primary-token:hover {
        border-color: var(--pwdl-brand-primary-hover);
        background: var(--pwdl-brand-primary-hover);
    }

    .pd-appshell-workspace-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .pd-appshell-status-bar {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
        min-height: 1.6rem;
    }

    .pd-appshell-status-pill {
        display: inline-flex;
        align-items: center;
        min-height: 1.45rem;
        border: 1px solid rgba(15, 118, 110, 0.18);
        border-radius: 999px;
        background: rgba(15, 118, 110, 0.08);
        padding-inline: 0.55rem;
        color: var(--pd-primary);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0;
    }

    .pd-appshell-action-toolbar {
        display: inline-flex;
        align-items: center;
        min-height: 2rem;
    }

    .pd-appshell-action-toolbar__rail {
        display: none;
        width: 1px;
        height: 1.75rem;
        background: var(--pd-border);
    }

    .fi-section,
    .fi-ta,
    .fi-fo-component-ctn {
        border-radius: var(--pwdl-control-radius);
    }

    /* Shared detail-page treatment for non-PMS record views. */
    .pd-standard-record-view {
        --pd-record-gap: 1rem;
    }

    .pd-standard-record-view .fi-header {
        align-items: center;
        gap: 1rem;
        margin-bottom: 0 !important;
        padding: 1rem 1.5rem !important;
        box-shadow: none;
    }

    .pd-standard-record-view .fi-header-heading {
        font-size: 1.65rem !important;
        line-height: 1.15 !important;
    }

    .pd-standard-record-view .fi-breadcrumbs {
        margin-bottom: 0.4rem;
    }

    .pd-standard-record-view .fi-header-actions,
    .pd-standard-record-view .fi-ac {
        align-items: center;
        gap: 0.5rem;
    }

    .pd-standard-record-view .fi-header-actions .fi-btn,
    .pd-standard-record-view .fi-ac .fi-btn {
        min-height: 2.4rem;
        border-radius: var(--pwdl-control-radius);
        font-weight: 750;
    }

    .pd-standard-record-view .fi-page-content {
        gap: var(--pd-record-gap);
        padding-top: var(--pd-record-gap);
    }

    .pd-standard-record-view .fi-sc,
    .pd-standard-record-view .fi-sc-component-ctn {
        gap: var(--pd-record-gap);
    }

    .pd-standard-record-view .fi-section {
        overflow: hidden;
        border: 1px solid var(--pwdl-border-subtle);
        border-radius: var(--pwdl-control-radius);
        background: var(--pwdl-surface-card);
        box-shadow: none;
    }

    .pd-standard-record-view .fi-section-header {
        padding: 0.95rem 1.1rem 0.8rem;
        border-bottom: 1px solid var(--pwdl-border-subtle);
        background: #fbfdff;
    }

    .pd-standard-record-view .fi-section-header-heading {
        color: var(--pwdl-text-primary);
        font-size: 0.95rem;
        font-weight: 800;
    }

    .pd-standard-record-view .fi-section-header-description {
        margin-top: 0.2rem;
        color: var(--pwdl-text-muted);
        font-size: 0.78rem;
        line-height: 1.45;
    }

    .pd-standard-record-view .fi-section-content {
        padding: 1rem 1.1rem;
    }

    .pd-standard-record-view .fi-in-entry-wrp-label {
        color: var(--pwdl-text-muted);
        font-size: 0.7rem;
        font-weight: 750;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .pd-standard-record-view .fi-in-entry-wrp-state {
        color: var(--pwdl-text-primary);
        font-size: 0.88rem;
        font-weight: 650;
        line-height: 1.45;
    }

    .pd-standard-record-view .fi-tabs {
        border: 1px solid var(--pwdl-border-subtle);
        border-radius: var(--pwdl-control-radius);
        background: var(--pwdl-surface-card);
        box-shadow: none;
    }

    .verification-reference-workspace {
        min-width: 0;
    }

    .verification-reference-workspace .fi-section,
    .verification-work-context,
    .verification-work-context-card {
        border-radius: var(--pwdl-control-radius);
    }

    .verification-work-context .pds-readonly-display {
        border-color: rgba(219, 228, 238, 0.95);
        background: #f8fafc;
    }

    .pds-work-context-panel {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        border: 1px solid rgba(219, 228, 238, 0.95);
        border-radius: 8px;
        background: #ffffff;
        box-shadow: var(--pd-shadow);
        padding: 0.85rem;
    }

    .pds-work-context-panel__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        border-bottom: 1px solid rgba(219, 228, 238, 0.78);
        padding-bottom: 0.75rem;
    }

    .pds-work-context-panel__header h2 {
        margin: 0;
        color: var(--pd-text);
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .pds-work-context-panel__header p {
        margin: 0.25rem 0 0;
        color: var(--pd-muted);
        font-size: 0.78rem;
        line-height: 1.45;
    }

    .pds-work-context-panel__cards {
        display: grid;
        gap: 0.65rem;
    }

    .pds-context-card {
        border: 1px solid rgba(219, 228, 238, 0.92);
        border-radius: 8px;
        background: #ffffff;
        overflow: hidden;
    }

    .pds-context-card--pinned {
        border-color: rgba(15, 118, 110, 0.26);
        background: #fbfefd;
    }

    .pds-context-card--disabled {
        opacity: 0.74;
    }

    .pds-context-card__summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        min-height: 2.75rem;
        padding: 0.7rem 0.8rem;
        cursor: pointer;
        list-style: none;
    }

    .pds-context-card__summary::-webkit-details-marker {
        display: none;
    }

    .pds-context-card__summary:focus-visible {
        outline: 2px solid rgba(15, 118, 110, 0.42);
        outline-offset: -2px;
    }

    .pds-context-card__title,
    .pds-context-card__meta {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .pds-context-card__title {
        min-width: 0;
        color: var(--pd-text);
        font-size: 0.82rem;
        font-weight: 800;
    }

    .pds-context-card__pin {
        border-radius: 999px;
        background: rgba(15, 118, 110, 0.08);
        padding: 0.12rem 0.4rem;
        color: var(--pd-primary);
        font-size: 0.65rem;
        font-weight: 800;
    }

    .pds-context-card__chevron {
        width: 0.48rem;
        height: 0.48rem;
        border-right: 2px solid #94a3b8;
        border-bottom: 2px solid #94a3b8;
        transform: rotate(45deg);
        transition: transform 120ms ease;
    }

    .pds-context-card[open] .pds-context-card__chevron {
        transform: rotate(225deg);
    }

    .pds-context-card__body {
        display: grid;
        gap: 0.65rem;
        border-top: 1px solid rgba(219, 228, 238, 0.72);
        padding: 0.75rem;
    }

    .pds-context-card--scrollable[open] .pds-context-card__body {
        max-height: 22rem;
        overflow-y: auto;
    }

    .pds-context-card__description,
    .pds-context-card__footer,
    .pds-context-card__empty span {
        margin: 0;
        color: var(--pd-muted);
        font-size: 0.76rem;
        line-height: 1.45;
    }

    .pds-context-card__rows {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
    }

    .pds-context-card__list {
        display: grid;
        gap: 0.5rem;
    }

    .pds-context-card__list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        border: 1px solid rgba(219, 228, 238, 0.86);
        border-radius: 8px;
        background: #f8fafc;
        padding: 0.6rem;
    }

    .pds-context-card__item-label {
        color: var(--pd-text);
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .pds-context-card__item-value {
        margin-top: 0.2rem;
        color: var(--pd-muted);
        font-size: 0.72rem;
        line-height: 1.4;
    }

    .pds-context-card__actions {
        justify-content: flex-end;
    }

    .pds-context-card__empty {
        display: grid;
        gap: 0.5rem;
    }

    .pd-focus-mode-active .fi-sidebar,
    .pd-focus-mode-active .fi-topbar,
    .pd-focus-mode-active .fi-header,
    .pd-focus-mode-active .pd-appshell-global-header,
    .pd-focus-mode-active .pd-appshell-workspace-header,
    .pd-focus-mode-active .pd-appshell-status-bar,
    .pd-focus-mode-active .pd-appshell-action-toolbar,
    .pd-focus-mode-active .pd-appshell-footer {
        display: none !important;
    }

    .pd-focus-mode-active .fi-main,
    .pd-focus-mode-active .fi-main-ctn,
    .pd-focus-mode-active .fi-page,
    .pd-focus-mode-active .fi-page-content {
        max-width: none !important;
    }

    .pd-focus-mode-active .fi-main {
        padding-top: 0 !important;
    }

    .pds-focus-mode-topbar {
        position: sticky;
        top: 0;
        z-index: 45;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem 1rem;
        align-items: center;
        border: 1px solid rgba(15, 118, 110, 0.18);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: var(--pd-shadow);
        padding: 0.85rem 1rem;
        backdrop-filter: blur(14px);
    }

    .pds-focus-mode-topbar__identity {
        min-width: 0;
    }

    .pds-focus-mode-topbar__eyebrow {
        margin-bottom: 0.35rem;
        color: var(--pd-primary);
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .pds-focus-mode-topbar__title-row,
    .pds-focus-mode-topbar__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .pds-focus-mode-topbar__title-row h1 {
        margin: 0;
        color: var(--pd-text);
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .pds-focus-mode-topbar__patient {
        color: #334155;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .pds-focus-mode-topbar__meta {
        justify-content: flex-end;
        white-space: nowrap;
    }

    .pds-sticky-action-bar {
        position: sticky;
        top: 4.75rem;
        z-index: 35;
        display: flex;
        justify-content: flex-end;
        border: 1px solid rgba(219, 228, 238, 0.92);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: var(--pd-shadow);
        padding: 0.65rem;
        backdrop-filter: blur(14px);
    }

    .pds-sticky-action-bar__actions {
        justify-content: flex-end;
    }

    .verification-focus-mode {
        gap: 0.85rem !important;
    }

    .verification-focus-mode .vt3-shell .uel2-shell {
        border-radius: 8px;
        box-shadow: none;
    }

    .verification-focus-mode .vt3-shell .uel2-shell__inner {
        padding: 8px;
    }

    .verification-focus-mode .vt3-shell .uel2-sidebar {
        top: 8.25rem;
    }

    .verification-focus-mode .vt3-shell .uel2-sidebar-rail {
        max-height: calc(100vh - 9rem);
        overflow-y: auto;
    }

    .verification-focus-mode .vt3-shell .uel2-header,
    .verification-focus-mode .vt3-shell .uel2-subsection__header,
    .verification-focus-mode .vt3-shell .uel2-table thead th {
        scroll-margin-top: 9rem;
    }

    .fi-resource-verifications .fi-ta {
        overflow: hidden;
        border: 1px solid var(--pwdl-border-subtle);
        border-radius: 12px;
        box-shadow: var(--pd-shadow);
    }

    .fi-resource-verifications .fi-ta-header,
    .fi-resource-verifications .fi-ta-toolbar {
        gap: 0.75rem;
    }

    .fi-resource-verifications .fi-ta-table {
        min-width: 1180px;
    }

    .fi-resource-verifications .fi-ta-header {
        position: sticky;
        top: 4rem;
        z-index: 20;
        background: color-mix(in srgb, var(--pwdl-surface-card) 96%, transparent);
        backdrop-filter: blur(14px);
    }

    .fi-resource-verifications .fi-ta-row:hover {
        background: #f0fdfa;
    }

    .fi-resource-verifications .fi-ta-cell,
    .fi-resource-verifications .fi-ta-header-cell {
        padding-block: 0.55rem;
    }

    .fi-resource-verifications .fi-ta-filters {
        position: sticky;
        top: 7.5rem;
        z-index: 19;
        border-radius: var(--pwdl-radius-lg);
        background: var(--pwdl-surface-card);
    }

    .fi-resource-verifications .fi-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem !important;
        padding-block: 0.75rem 0.25rem !important;
    }

    .fi-resource-verifications .fi-header-heading {
        font-size: clamp(1.8rem, 2.3vw, 2.15rem) !important;
        line-height: 1.05 !important;
    }

    .fi-resource-verifications .fi-header-subheading {
        margin-top: 0.35rem !important;
        color: #475569 !important;
        font-size: 0.92rem !important;
    }

    .fi-resource-verifications .fi-header-actions,
    .fi-resource-verifications .fi-ac {
        align-items: center;
    }

    .fi-resource-verifications .fi-header-actions .fi-btn,
    .fi-resource-verifications .fi-ac .fi-btn {
        min-height: 2.45rem;
        border-radius: 10px;
        font-weight: 800;
    }

    .verification-queue-kpis {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #e3edf5;
        background: #ffffff;
    }

    .verification-queue-kpi {
        display: inline-flex;
        align-items: center;
        min-height: 1.65rem;
        padding: 0.28rem 0.72rem;
        border: 1px solid #dbe4ee;
        border-radius: 999px;
        background: #f8fafc;
        color: #334155;
        font-size: 0.75rem;
        font-weight: 850;
        white-space: nowrap;
    }

    .verification-queue-kpi--all {
        border-color: #8fd8cf;
        background: #e9fbf7;
        color: #00796b;
    }

    .verification-queue-kpi--pending {
        border-color: #f4b860;
        background: #fff7e6;
        color: #a34f00;
    }

    .verification-queue-kpi--progress {
        border-color: #bfdbfe;
        background: #eef6ff;
        color: #1d4ed8;
    }

    .verification-queue-kpi--waiting {
        border-color: #fed7aa;
        background: #fff7ed;
        color: #c2410c;
    }

    .verification-queue-kpi--complete {
        border-color: #bbf7d0;
        background: #ecfdf5;
        color: #047857;
    }

    .pds-workspace-shell {
        display: grid;
        gap: var(--pwdl-space-xl);
        width: 100%;
        min-width: 0;
    }

    .pds-workspace-header,
    .pds-workspace-toolbar,
    .pds-workspace-panel,
    .pds-workspace-center,
    .pds-workspace-footer {
        border: 1px solid var(--pwdl-border-subtle);
        border-radius: var(--pwdl-radius-lg);
        background: var(--pwdl-surface-card);
        box-shadow: var(--pd-shadow);
    }

    .pds-workspace-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--pwdl-space-lg);
        padding: var(--pwdl-space-xl);
    }

    .pds-workspace-header__copy {
        min-width: 0;
    }

    .pds-workspace-header__eyebrow {
        display: inline-flex;
        margin-bottom: var(--pwdl-space-sm);
        color: var(--pwdl-brand-primary);
        font-size: var(--pwdl-font-size-caption);
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .pds-workspace-header__title {
        margin: 0;
        color: var(--pwdl-text-primary);
        font-size: var(--pwdl-font-size-workspace-title);
        font-weight: 800;
        line-height: 1.2;
    }

    .pds-workspace-header__description {
        margin: var(--pwdl-space-sm) 0 0;
        color: var(--pwdl-text-secondary);
        font-size: var(--pwdl-font-size-body);
        line-height: 1.5;
    }

    .pds-workspace-header__actions {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: var(--pwdl-space-sm);
    }

    .pds-workspace-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: var(--pwdl-space-md);
        padding: var(--pwdl-space-lg);
    }

    .pds-workspace-body {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: var(--pwdl-layout-gap);
        min-width: 0;
    }

    .pds-workspace-panel,
    .pds-workspace-center {
        min-width: 0;
        padding: var(--pwdl-space-lg);
    }

    .pds-workspace-panel {
        align-self: start;
    }

    .pds-workspace-center {
        min-height: 24rem;
    }

    .pds-workspace-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: var(--pwdl-space-sm);
        padding: var(--pwdl-space-md) var(--pwdl-space-lg);
        color: var(--pwdl-text-muted);
        font-size: var(--pwdl-font-size-caption);
    }

    .pwdl-workspace-toolbar {
        align-items: flex-start;
        border-color: color-mix(in srgb, var(--pwdl-brand-primary) 18%, var(--pwdl-border-subtle));
        border-radius: var(--pwdl-radius-lg);
        background: var(--pwdl-surface-card);
        box-shadow: var(--pd-shadow);
        padding: var(--pwdl-space-lg);
    }

    .pwdl-workspace-toolbar__eyebrow {
        display: inline-flex;
        margin-bottom: 0.3rem;
        color: var(--pwdl-brand-primary);
        font-size: 0.72rem;
        font-weight: 850;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .pwdl-workspace-toolbar__legend {
        justify-content: flex-end;
    }

    .pd-appshell-footer {
        display: flex;
        min-height: 26px;
        max-height: 28px;
        align-items: center;
        gap: 0.75rem;
        overflow: hidden;
        border-top: 1px solid rgba(219, 228, 238, 0.86);
        padding: 0 1rem;
        color: var(--pd-muted);
        font-size: 0.72rem;
        line-height: 1;
        background: rgba(255, 255, 255, 0.92);
    }

    .pd-appshell-footer span + span::before {
        content: "";
        display: inline-block;
        width: 3px;
        height: 3px;
        margin-right: 0.75rem;
        border-radius: 999px;
        background: #cbd5e1;
        vertical-align: middle;
    }

    @media (min-width: 1536px) {
        .pds-workspace-body {
            grid-template-columns: var(--pwdl-layout-left) minmax(0, 1fr) var(--pwdl-layout-right);
        }

        .pds-workspace-body--no-left {
            grid-template-columns: minmax(0, 1fr) var(--pwdl-layout-right);
        }

        .pds-workspace-body--no-right {
            grid-template-columns: var(--pwdl-layout-left) minmax(0, 1fr);
        }

        .pds-workspace-body--no-left.pds-workspace-body--no-right {
            grid-template-columns: minmax(0, 1fr);
        }

        .pds-workspace-panel--right {
            position: sticky;
            top: calc(var(--pwdl-shell-topbar) + var(--pwdl-space-lg));
        }
    }

    @media (max-width: 768px) {
        .pds-workspace-header {
            flex-direction: column;
        }

        .pds-workspace-header__actions {
            justify-content: flex-start;
            width: 100%;
        }

        .pd-appshell-global-header {
            grid-template-columns: max-content minmax(0, 1fr) max-content;
            gap: var(--pwdl-space-sm);
            white-space: normal;
        }

        .pd-appshell-global-header__brand {
            min-width: auto;
        }

        .pd-appshell-global-header__brand-name {
            display: none;
        }

        .pd-appshell-global-header__search {
            grid-column: 1 / -1;
            grid-row: 2;
            width: 100%;
        }

        .pd-appshell-slot--search {
            width: 100%;
            max-width: none;
        }

        .pd-appshell-global-header__utilities {
            justify-content: flex-end;
        }

        .pd-appshell-global-header__utilities .pd-appshell-slot {
            display: none;
        }

        .fi-header {
            padding: 0.9rem;
        }

        .pd-standard-record-view .fi-header {
            align-items: flex-start;
            padding: 0.9rem !important;
        }

        .pd-standard-record-view .fi-header-actions {
            width: 100%;
        }

        .pd-standard-record-view .fi-header-actions .fi-btn {
            flex: 1 1 auto;
        }

        .pd-standard-record-view .fi-section-header,
        .pd-standard-record-view .fi-section-content {
            padding-inline: 0.9rem;
        }

        .pd-appshell-footer {
            gap: 0.45rem;
            padding-inline: 0.75rem;
        }

        .pds-context-card__rows {
            grid-template-columns: minmax(0, 1fr);
        }

        .pds-focus-mode-topbar,
        .pds-sticky-action-bar {
            position: static;
        }

        .pds-focus-mode-topbar {
            grid-template-columns: minmax(0, 1fr);
        }

        .pds-focus-mode-topbar__meta,
        .pds-sticky-action-bar,
        .pds-sticky-action-bar__actions {
            justify-content: flex-start;
        }

        .verification-focus-mode .vt3-shell .uel2-sidebar-rail {
            max-height: none;
        }
    }

    /*
     * Shared AppShell header final alignment.
     * The sidebar owns branding on desktop; the header owns workspace switching,
     * global search, and account utilities.
     */
    .fi-topbar:has(.pd-appshell-global-header) {
        position: sticky !important;
        top: 0 !important;
        z-index: 45 !important;
        display: flex !important;
        min-height: 4rem !important;
        height: 4rem !important;
        align-items: center !important;
        border-bottom: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: none !important;
        padding: 0 1.5rem !important;
    }

    .pd-appshell-global-header {
        display: grid !important;
        width: 100% !important;
        min-height: 4rem !important;
        height: 4rem !important;
        align-items: center !important;
        grid-template-columns: max-content minmax(18rem, 36rem) max-content !important;
        justify-content: space-between !important;
        gap: clamp(0.75rem, 2vw, 2rem) !important;
        padding: 0 !important;
        white-space: nowrap !important;
    }

    .pd-appshell-global-header__left,
    .pd-appshell-global-header__utilities {
        display: inline-flex !important;
        min-width: 0 !important;
        align-items: center !important;
    }

    .pd-appshell-global-header__left {
        justify-self: start !important;
    }

    .pd-appshell-global-header__search {
        display: inline-flex !important;
        justify-self: center !important;
        width: min(100%, 36rem) !important;
        min-width: 18rem !important;
        height: 2.5rem !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 0.55rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.8rem !important;
        background: #f8fafc !important;
        padding: 0 0.55rem 0 0.85rem !important;
        color: #94a3b8 !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75) !important;
    }

    .pd-appshell-global-header__search-icon {
        width: 1rem !important;
        height: 1rem !important;
        color: #64748b !important;
    }

    .pd-appshell-global-header__search-text {
        overflow: hidden !important;
        flex: 1 1 auto !important;
        color: #94a3b8 !important;
        font-size: 0.78rem !important;
        font-weight: 750 !important;
        line-height: 1 !important;
        text-overflow: ellipsis !important;
    }

    .pd-appshell-global-header__kbd {
        display: inline-flex !important;
        height: 1.45rem !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.45rem !important;
        background: #ffffff !important;
        padding: 0 0.45rem !important;
        color: #64748b !important;
        font-size: 0.68rem !important;
        font-weight: 850 !important;
    }

    .pd-appshell-global-header__utilities {
        justify-self: end !important;
        justify-content: flex-end !important;
        gap: 0.6rem !important;
    }

    .pd-appshell-icon-button {
        display: inline-flex !important;
        width: 2.5rem !important;
        height: 2.5rem !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.8rem !important;
        background: #ffffff !important;
        color: #334155 !important;
        cursor: pointer !important;
    }

    .pd-appshell-icon-button:hover,
    .pd-appshell-workspace-switcher__summary:hover,
    .pd-appshell-user-menu__summary:hover {
        border-color: #bfdbfe !important;
        background: #f8fafc !important;
    }

    .pd-appshell-icon-button__icon,
    .pd-appshell-workspace-switcher__icon,
    .pd-appshell-user-menu__chevron {
        width: 1rem !important;
        height: 1rem !important;
    }

    .pd-appshell-workspace-switcher__summary {
        min-width: 9.5rem !important;
        min-height: 2.5rem !important;
        height: 2.5rem !important;
        justify-content: space-between !important;
        border-color: #dbe4ee !important;
        border-radius: 0.8rem !important;
        padding: 0 0.85rem !important;
        color: #334155 !important;
        font-size: 0.8rem !important;
    }

    .pd-appshell-user-menu__summary {
        min-height: 2.5rem !important;
        gap: 0.55rem !important;
        border-radius: 0.8rem !important;
        padding: 0 0.25rem 0 0.35rem !important;
    }

    .pd-appshell-user-menu__avatar {
        width: 2.35rem !important;
        height: 2.35rem !important;
        flex: 0 0 auto !important;
    }

    .pd-appshell-user-menu__summary-text {
        display: grid !important;
        min-width: 0 !important;
        gap: 0.05rem !important;
    }

    .pd-appshell-user-menu__summary-name,
    .pd-appshell-user-menu__summary-role {
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .pd-appshell-user-menu__summary-name {
        color: #101828 !important;
        font-size: 0.8rem !important;
        font-weight: 850 !important;
        line-height: 1.05 !important;
    }

    .pd-appshell-user-menu__summary-role {
        color: #64748b !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        line-height: 1.05 !important;
    }

    @media (min-width: 1024px) {
        .fi-topbar:has(.pd-appshell-global-header) {
            margin-inline-start: var(--pd-sidebar-expanded) !important;
            width: calc(100% - var(--pd-sidebar-expanded)) !important;
        }

        html.app-sidebar-collapsed .fi-topbar:has(.pd-appshell-global-header) {
            margin-inline-start: var(--pd-sidebar-collapsed) !important;
            width: calc(100% - var(--pd-sidebar-collapsed)) !important;
        }

        .pd-appshell-global-header__brand {
            display: none !important;
        }
    }

    @media (max-width: 1023px) {
        .fi-topbar:has(.pd-appshell-global-header) {
            height: auto !important;
            min-height: 4rem !important;
            padding: 0.65rem 0.9rem !important;
        }

        .pd-appshell-global-header {
            height: auto !important;
            min-height: 0 !important;
            grid-template-columns: minmax(0, 1fr) max-content !important;
            row-gap: 0.65rem !important;
            white-space: normal !important;
        }

        .pd-appshell-global-header__search {
            grid-column: 1 / -1 !important;
            grid-row: 2 !important;
            width: 100% !important;
            min-width: 0 !important;
        }

        .pd-appshell-user-menu__summary-text,
        .pd-appshell-user-menu__chevron {
            display: none !important;
        }
    }
</style>
