<style>
    .fi-main-sidebar {
        background:
            linear-gradient(180deg, rgba(255, 252, 245, 0.94) 0%, rgba(255, 255, 255, 0.98) 100%);
        border-inline-end: 1px solid rgba(222, 226, 233, 0.95);
        box-shadow:
            inset -1px 0 0 rgba(255, 255, 255, 0.75),
            14px 0 34px rgba(15, 23, 42, 0.035);
        transition: width 0.24s ease, min-width 0.24s ease, max-width 0.24s ease, box-shadow 0.24s ease;
    }

    .fi-sidebar-header {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.95);
        background: transparent;
    }

    .fi-sidebar-header-logo-ctn {
        flex: 1 1 auto;
        min-width: 0;
        transition: opacity 0.16s ease, max-width 0.16s ease, transform 0.16s ease;
    }

    .admin-sidebar-greeting {
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
        flex: 1 1 auto;
        transition: opacity 0.16s ease, max-width 0.16s ease, transform 0.16s ease;
    }

    .admin-sidebar-greeting__avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 999px;
        background: linear-gradient(180deg, #fff7dd 0%, #ffe9a8 100%);
        border: 1px solid rgba(245, 199, 108, 0.55);
        color: #8b5e00;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        flex-shrink: 0;
    }

    .admin-sidebar-greeting__body {
        display: flex;
        flex-direction: column;
        gap: 0.08rem;
        min-width: 0;
        flex: 1 1 auto;
    }

    .admin-sidebar-greeting__name {
        font-size: 0.95rem;
        line-height: 1.2;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .admin-sidebar-greeting__role {
        font-size: 0.73rem;
        line-height: 1.15;
        font-weight: 600;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fi-sidebar-nav {
        padding-inline-end: 0.25rem;
        row-gap: 0 !important;
    }

    .fi-sidebar-nav-groups {
        row-gap: 0 !important;
    }

    .fi-sidebar-group {
        gap: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    .fi-sidebar-group-btn {
        padding-top: 0.15rem !important;
        padding-bottom: 0.15rem !important;
    }

    .fi-sidebar-group-label {
        display: flex;
        align-items: center;
        color: #94a3b8;
        font-size: 0.79rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        margin-bottom: 0.15rem;
    }

    .fi-sidebar-group > ul,
    .fi-sidebar-group > ol,
    .fi-sidebar-group > div {
        margin-top: 0 !important;
    }

    .fi-main-ctn {
        background: linear-gradient(180deg, #fcfcfd 0%, #ffffff 100%);
        transition: padding-inline-start 0.24s ease;
    }

    .fi-main.fi-width-7xl {
        max-width: none !important;
    }

    @media (min-width: 1024px) {
        .fi-sidebar-header-logo-ctn {
            display: none;
        }
    }

    .fi-sidebar-nav,
    .fi-sidebar-nav-groups,
    .fi-main-sidebar {
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    .fi-sidebar-nav::-webkit-scrollbar,
    .fi-sidebar-nav-groups::-webkit-scrollbar,
    .fi-main-sidebar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .fi-sidebar-nav::-webkit-scrollbar-track,
    .fi-sidebar-nav-groups::-webkit-scrollbar-track,
    .fi-main-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .fi-sidebar-nav::-webkit-scrollbar-thumb,
    .fi-sidebar-nav-groups::-webkit-scrollbar-thumb,
    .fi-main-sidebar::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 999px;
        transition: background 0.18s ease;
    }

    .fi-sidebar-nav:hover,
    .fi-sidebar-nav-groups:hover,
    .fi-main-sidebar:hover {
        scrollbar-color: #94a3b8 transparent;
    }

    .fi-sidebar-nav:hover::-webkit-scrollbar-thumb,
    .fi-sidebar-nav-groups:hover::-webkit-scrollbar-thumb,
    .fi-main-sidebar:hover::-webkit-scrollbar-thumb {
        background: #94a3b8;
    }

    .fi-sidebar-nav:hover::-webkit-scrollbar-thumb:hover,
    .fi-sidebar-nav-groups:hover::-webkit-scrollbar-thumb:hover,
    .fi-main-sidebar:hover::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .admin-sidebar-header-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-inline-start: auto;
        flex-shrink: 0;
    }

    .admin-sidebar-toggle-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.8rem;
        border: 1px solid rgba(216, 222, 232, 0.95);
        background: rgba(255, 255, 255, 0.92);
        color: #64748b;
        cursor: pointer;
        transition: border-color 0.18s ease, color 0.18s ease, background 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }

    .admin-sidebar-toggle-btn:hover {
        border-color: rgba(245, 199, 108, 0.9);
        background: #fffdf7;
        color: #8b5e00;
        transform: translateY(-1px);
    }

    .admin-sidebar-toggle-btn:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(245, 199, 108, 0.18);
    }

    .admin-sidebar-toggle-icon {
        position: absolute;
        inset: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.18s ease, transform 0.18s ease;
    }

    .admin-sidebar-toggle-icon--expand {
        opacity: 0;
        transform: scale(0.92);
        pointer-events: none;
    }

    html.verification-sidebar-collapsed .fi-main-sidebar {
        width: 4rem !important;
        min-width: 4rem !important;
        max-width: 4rem !important;
        transform: none;
        opacity: 1;
        pointer-events: auto;
        box-shadow: 8px 0 20px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    html.verification-sidebar-collapsed .fi-main-ctn {
        padding-inline-start: 0 !important;
    }

    html.verification-sidebar-collapsed .fi-sidebar-header {
        justify-content: center;
        padding-inline: 0.45rem;
    }

    html.verification-sidebar-collapsed .fi-sidebar-header-logo-ctn {
        opacity: 0;
        max-width: 0;
        overflow: hidden;
        transform: translateX(-8px);
        pointer-events: none;
    }

    html.verification-sidebar-collapsed .admin-sidebar-greeting {
        opacity: 0;
        max-width: 0;
        overflow: hidden;
        transform: translateX(-8px);
        pointer-events: none;
    }

    html.verification-sidebar-collapsed .admin-workspace-scope-wrap,
    html.verification-sidebar-collapsed .fi-sidebar-nav-groups,
    html.verification-sidebar-collapsed .fi-sidebar-footer {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateX(-10px);
    }

    html.verification-sidebar-collapsed .admin-sidebar-header-toggle {
        margin-inline-start: 0;
    }

    html.verification-sidebar-collapsed .admin-sidebar-toggle-icon--collapse {
        opacity: 0;
        transform: scale(0.92);
        pointer-events: none;
    }

    html.verification-sidebar-collapsed .admin-sidebar-toggle-icon--expand {
        opacity: 1;
        transform: scale(1);
    }

    .admin-workspace-scope-wrap,
    .fi-sidebar-nav-groups,
    .fi-sidebar-footer {
        transition: opacity 0.14s ease, transform 0.14s ease, visibility 0.14s ease;
    }

    html.dark .fi-main-sidebar {
        background:
            linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(17, 24, 39, 0.98) 100%);
        border-inline-end: 1px solid rgba(51, 65, 85, 0.95);
        box-shadow:
            inset -1px 0 0 rgba(255, 255, 255, 0.03),
            14px 0 34px rgba(2, 6, 23, 0.35);
    }

    html.dark .fi-sidebar-header {
        border-bottom-color: rgba(51, 65, 85, 0.95);
    }

    html.dark .fi-sidebar-group-label {
        color: #64748b;
    }

    html.dark .fi-main-ctn {
        background: linear-gradient(180deg, #020617 0%, #0f172a 100%);
    }

    html.dark .fi-sidebar-nav:hover,
    html.dark .fi-sidebar-nav-groups:hover,
    html.dark .fi-main-sidebar:hover {
        scrollbar-color: #475569 transparent;
    }

    html.dark .fi-sidebar-nav:hover::-webkit-scrollbar-thumb,
    html.dark .fi-sidebar-nav-groups:hover::-webkit-scrollbar-thumb,
    html.dark .fi-main-sidebar:hover::-webkit-scrollbar-thumb {
        background: #475569;
    }

    html.dark .fi-sidebar-nav:hover::-webkit-scrollbar-thumb:hover,
    html.dark .fi-sidebar-nav-groups:hover::-webkit-scrollbar-thumb:hover,
    html.dark .fi-main-sidebar:hover::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    html.dark .admin-sidebar-toggle-btn {
        border-color: rgba(255, 255, 255, 0.1);
        background: rgba(15, 23, 42, 0.88);
        color: #cbd5e1;
        box-shadow: none;
    }

    html.dark .admin-sidebar-toggle-btn:hover {
        border-color: rgba(250, 204, 21, 0.38);
        color: #f8d17d;
        background: rgba(30, 41, 59, 0.94);
    }

    html.dark .admin-sidebar-greeting__avatar {
        background: linear-gradient(180deg, rgba(250, 204, 21, 0.15) 0%, rgba(245, 158, 11, 0.18) 100%);
        border-color: rgba(250, 204, 21, 0.22);
        color: #f8d17d;
    }

    html.dark .admin-sidebar-greeting__name {
        color: #f8fafc;
    }

    html.dark .admin-sidebar-greeting__role {
        color: #64748b;
    }
</style>
