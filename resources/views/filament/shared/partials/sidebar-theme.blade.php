<style>
    .fi-main-sidebar {
        background: linear-gradient(180deg, rgba(255, 252, 245, 0.96) 0%, rgba(255, 255, 255, 0.985) 100%);
        border-inline-end: 1px solid rgba(222, 226, 233, 0.95);
        box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.75), 14px 0 34px rgba(15, 23, 42, 0.035);
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

    @media (min-width: 1024px) {
        .fi-sidebar-header-logo-ctn {
            display: none;
        }
    }

    .app-sidebar-greeting {
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
        flex: 1 1 auto;
        transition: opacity 0.16s ease, max-width 0.16s ease, transform 0.16s ease;
    }

    .app-sidebar-greeting__avatar {
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
        font-weight: 900;
        letter-spacing: 0.06em;
        flex-shrink: 0;
    }

    .app-sidebar-greeting__body {
        display: flex;
        flex-direction: column;
        gap: 0.08rem;
        min-width: 0;
        flex: 1 1 auto;
    }

    .app-sidebar-greeting__hello {
        font-size: 0.95rem;
        line-height: 1.2;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .app-sidebar-greeting__role {
        font-size: 0.73rem;
        line-height: 1.15;
        font-weight: 700;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fi-sidebar-nav,
    .fi-sidebar-nav-groups {
        padding-inline-end: 0.25rem;
        row-gap: 0 !important;
    }

    .fi-sidebar-nav-groups,
    .fi-sidebar-group {
        row-gap: 0 !important;
        gap: 0 !important;
    }

    .fi-sidebar-group {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    .fi-sidebar-group-btn {
        padding-top: 0.15rem !important;
        padding-bottom: 0.15rem !important;
    }

    .fi-sidebar-group-label {
        color: #94a3b8;
        font-size: 0.79rem;
        font-weight: 800;
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

    .fi-main,
    .fi-main.fi-width-7xl,
    .fi-main.fi-width-full,
    .fi-main[class*="fi-width-"] {
        width: 100% !important;
        max-width: none !important;
    }

    .fi-main > .fi-page,
    .fi-main > .fi-page-sub-navigation-sidebar-ctn,
    .fi-main > .fi-page-with-sub-navigation {
        width: 100% !important;
        max-width: none !important;
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
    .fi-main-sidebar::-webkit-scrollbar-track,
    .fi-sidebar-nav::-webkit-scrollbar-thumb,
    .fi-sidebar-nav-groups::-webkit-scrollbar-thumb,
    .fi-main-sidebar::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 999px;
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

    .app-sidebar-header-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-inline-start: auto;
        flex-shrink: 0;
    }

    .app-sidebar-toggle-btn {
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

    .app-sidebar-toggle-btn:hover {
        border-color: rgba(245, 199, 108, 0.9);
        background: #fffdf7;
        color: #8b5e00;
        transform: translateY(-1px);
    }

    .app-sidebar-toggle-icon {
        position: absolute;
        inset: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.18s ease, transform 0.18s ease;
    }

    .app-sidebar-toggle-icon--expand {
        opacity: 0;
        transform: scale(0.92);
        pointer-events: none;
    }

    html.app-sidebar-collapsed .fi-main-sidebar {
        width: 4rem !important;
        min-width: 4rem !important;
        max-width: 4rem !important;
        transform: none;
        opacity: 1;
        pointer-events: auto;
        box-shadow: 8px 0 20px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    html.app-sidebar-collapsed .fi-main-ctn {
        padding-inline-start: 0 !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-header {
        justify-content: center;
        padding-inline: 0.45rem;
    }

    html.app-sidebar-collapsed .fi-sidebar-header-logo-ctn,
    html.app-sidebar-collapsed .app-sidebar-greeting,
    html.app-sidebar-collapsed .admin-workspace-scope-wrap,
    html.app-sidebar-collapsed .clinic-workspace-scope-wrap,
    html.app-sidebar-collapsed .fi-sidebar-nav-groups,
    html.app-sidebar-collapsed .fi-sidebar-footer {
        opacity: 0;
        visibility: hidden;
        max-width: 0;
        overflow: hidden;
        pointer-events: none;
        transform: translateX(-10px);
    }

    html.app-sidebar-collapsed .app-sidebar-header-toggle {
        margin-inline-start: 0;
    }

    html.app-sidebar-collapsed .app-sidebar-toggle-icon--collapse {
        opacity: 0;
        transform: scale(0.92);
        pointer-events: none;
    }

    html.app-sidebar-collapsed .app-sidebar-toggle-icon--expand {
        opacity: 1;
        transform: scale(1);
    }

    .admin-workspace-scope-wrap,
    .clinic-workspace-scope-wrap,
    .fi-sidebar-nav-groups,
    .fi-sidebar-footer {
        transition: opacity 0.14s ease, transform 0.14s ease, visibility 0.14s ease;
    }

    html.dark .fi-main-sidebar {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(17, 24, 39, 0.98) 100%);
        border-inline-end: 1px solid rgba(51, 65, 85, 0.95);
        box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.03), 14px 0 34px rgba(2, 6, 23, 0.35);
    }

    html.dark .fi-sidebar-header {
        border-bottom-color: rgba(51, 65, 85, 0.95);
    }

    html.dark .fi-sidebar-group-label,
    html.dark .app-sidebar-greeting__role {
        color: #64748b;
    }

    html.dark .fi-main-ctn {
        background: linear-gradient(180deg, #020617 0%, #0f172a 100%);
    }

    html.dark .app-sidebar-toggle-btn {
        border-color: rgba(255, 255, 255, 0.1);
        background: rgba(15, 23, 42, 0.88);
        color: #cbd5e1;
        box-shadow: none;
    }

    html.dark .app-sidebar-toggle-btn:hover {
        border-color: rgba(250, 204, 21, 0.38);
        color: #f8d17d;
        background: rgba(30, 41, 59, 0.94);
    }

    html.dark .app-sidebar-greeting__avatar {
        background: linear-gradient(180deg, rgba(250, 204, 21, 0.15) 0%, rgba(245, 158, 11, 0.18) 100%);
        border-color: rgba(250, 204, 21, 0.22);
        color: #f8d17d;
    }

    html.dark .app-sidebar-greeting__hello {
        color: #f8fafc;
    }

    /*
     * Shared active-panel shell polish.
     * Applies to SaaS, Verification, Clinic, and DSO through AppShell.
     */
    .fi-topbar {
        min-height: 4.25rem !important;
        border-bottom: 1px solid #dbe4ee !important;
        background: rgba(255, 255, 255, 0.98) !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05) !important;
    }

    .fi-topbar:has(.pd-appshell-global-header) {
        padding-inline: 0.85rem !important;
    }

    .pd-appshell-global-header {
        grid-template-columns: 13rem max-content minmax(20rem, 1fr) max-content !important;
        gap: 1rem !important;
        min-height: 3.5rem !important;
        padding-inline: 0.25rem !important;
    }

    .pd-appshell-global-header__brand {
        min-width: 0 !important;
        gap: 0.65rem !important;
        font-size: 1rem !important;
    }

    .pd-appshell-global-header__logo {
        width: 2.35rem !important;
        height: 2.35rem !important;
        border-radius: 0.85rem !important;
        background: #0f766e !important;
        box-shadow: 0 10px 24px rgba(15, 118, 110, 0.16) !important;
    }

    .pd-appshell-workspace-switcher__summary,
    .pd-appshell-slot,
    .pd-appshell-user-menu__avatar {
        border-color: #dbe4ee !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    }

    .pd-appshell-workspace-switcher__summary {
        min-height: 2.45rem !important;
        padding-inline: 0.9rem !important;
        background: #ffffff !important;
        color: #334155 !important;
    }

    .pd-appshell-slot--search {
        width: min(100%, 40rem) !important;
        max-width: 40rem !important;
        min-height: 2.45rem !important;
        background: #f8fafc !important;
    }

    .pd-appshell-global-header__utilities {
        gap: 0.55rem !important;
    }

    .pd-appshell-user-menu__panel,
    .pd-appshell-workspace-switcher__menu {
        border-color: #dbe4ee !important;
        border-radius: 0.85rem !important;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16) !important;
    }

    .fi-main-sidebar {
        background: #ffffff !important;
        border-inline-end: 1px solid #dbe4ee !important;
        box-shadow: 12px 0 30px rgba(15, 23, 42, 0.035) !important;
    }

    .fi-sidebar-header {
        min-height: 4.75rem !important;
        padding-inline: 1rem !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    .app-sidebar-greeting__avatar {
        background: #ecfeff !important;
        border-color: #99f6e4 !important;
        color: #0f766e !important;
        box-shadow: none !important;
    }

    .app-sidebar-greeting__hello {
        color: #0f172a !important;
    }

    .app-sidebar-greeting__role {
        color: #64748b !important;
    }

    .fi-sidebar-nav {
        padding: 0.85rem 0.75rem 1.25rem !important;
    }

    .fi-sidebar-group-label {
        color: #64748b !important;
        font-size: 0.76rem !important;
        font-weight: 850 !important;
        letter-spacing: 0 !important;
    }

    .fi-sidebar-item a {
        min-height: 2.45rem !important;
        border-radius: 0.75rem !important;
        color: #334155 !important;
        font-weight: 750 !important;
    }

    .fi-sidebar-item a:hover {
        background: #f1f5f9 !important;
        color: #0f172a !important;
    }

    .fi-sidebar-item-active a,
    .fi-sidebar-item.fi-active a,
    .fi-sidebar-item[aria-current="page"] a {
        border-left: 0 !important;
        background: #ecfdf5 !important;
        color: #0f766e !important;
        box-shadow: inset 3px 0 0 #0f766e !important;
    }

    .fi-main-ctn {
        background: #f8fafc !important;
    }

    .fi-main {
        padding-inline: clamp(1rem, 2vw, 2rem) !important;
    }

    @media (max-width: 980px) {
        .pd-appshell-global-header {
            grid-template-columns: max-content minmax(0, 1fr) max-content !important;
        }

        .pd-appshell-global-header__search {
            display: none !important;
        }
    }

    /*
     * Product shell standard.
     * This is the shared header/sidebar treatment for the active panels.
     */
    .fi-topbar:has(.pd-appshell-global-header) {
        min-height: 4.35rem !important;
        padding: 0 1.35rem !important;
        background: #ffffff !important;
        border-bottom: 1px solid #dbe4ee !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    }

    .fi-topbar:has(.pd-appshell-global-header) > nav,
    .fi-topbar:has(.pd-appshell-global-header) > div {
        width: 100% !important;
        max-width: none !important;
    }

    .pd-appshell-global-header {
        display: grid !important;
        grid-template-columns: minmax(17rem, 22rem) minmax(24rem, 42rem) max-content !important;
        align-items: center !important;
        column-gap: clamp(1rem, 3vw, 3rem) !important;
        min-height: 4.35rem !important;
        width: 100% !important;
        padding: 0 !important;
        color: #334155 !important;
        white-space: nowrap !important;
    }

    .pd-appshell-global-header__left {
        display: inline-flex !important;
        align-items: center !important;
        gap: 1rem !important;
        min-width: 0 !important;
    }

    .pd-appshell-global-header__brand {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.7rem !important;
        min-width: 0 !important;
        color: #0f172a !important;
        font-size: 1.08rem !important;
        font-weight: 850 !important;
        text-decoration: none !important;
    }

    .pd-appshell-global-header__brand:hover {
        color: #0f172a !important;
    }

    .pd-appshell-global-header__logo {
        width: 2.35rem !important;
        height: 2.35rem !important;
        border-radius: 0.85rem !important;
        background: #0f766e !important;
        color: #ffffff !important;
        font-size: 0.76rem !important;
        font-weight: 900 !important;
        box-shadow: 0 10px 22px rgba(15, 118, 110, 0.16) !important;
    }

    .pd-appshell-global-header__brand-name {
        color: #0f172a !important;
        font-weight: 850 !important;
    }

    .pd-appshell-workspace-switcher {
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
        flex-shrink: 0 !important;
    }

    .pd-appshell-workspace-switcher__summary {
        display: inline-flex !important;
        min-height: 2.55rem !important;
        align-items: center !important;
        gap: 0.5rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.65rem !important;
        background: #ffffff !important;
        padding: 0 0.9rem !important;
        color: #334155 !important;
        font-size: 0.82rem !important;
        font-weight: 800 !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    }

    .pd-appshell-workspace-switcher__icon,
    .pd-appshell-user-menu__chevron {
        width: 0.9rem !important;
        height: 0.9rem !important;
        color: #64748b !important;
    }

    .pd-appshell-global-header__search {
        display: inline-flex !important;
        align-items: center !important;
        justify-self: center !important;
        width: min(100%, 42rem) !important;
        min-height: 2.55rem !important;
        gap: 0.65rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.65rem !important;
        background: #f8fafc !important;
        padding: 0 0.75rem !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 1px 2px rgba(15, 23, 42, 0.035) !important;
    }

    .pd-appshell-global-header__search-icon {
        width: 1.05rem !important;
        height: 1.05rem !important;
        color: #94a3b8 !important;
        flex-shrink: 0 !important;
    }

    .pd-appshell-global-header__search-text {
        min-width: 0 !important;
        flex: 1 1 auto !important;
        overflow: hidden !important;
        color: #94a3b8 !important;
        font-size: 0.78rem !important;
        font-weight: 650 !important;
        text-overflow: ellipsis !important;
    }

    .pd-appshell-global-header__kbd {
        display: inline-flex !important;
        min-height: 1.55rem !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.45rem !important;
        background: #ffffff !important;
        padding: 0 0.5rem !important;
        color: #64748b !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
    }

    .pd-appshell-global-header__utilities {
        display: inline-flex !important;
        align-items: center !important;
        justify-self: end !important;
        gap: 0.55rem !important;
        min-width: 0 !important;
    }

    .pd-appshell-icon-button {
        display: inline-flex !important;
        width: 2.45rem !important;
        height: 2.45rem !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.65rem !important;
        background: #ffffff !important;
        color: #334155 !important;
        cursor: pointer !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    }

    .pd-appshell-icon-button:hover {
        border-color: #99f6e4 !important;
        background: #f0fdfa !important;
        color: #0f766e !important;
    }

    .pd-appshell-icon-button__icon {
        width: 1.1rem !important;
        height: 1.1rem !important;
    }

    .pd-appshell-user-menu__summary {
        display: inline-flex !important;
        min-height: 2.55rem !important;
        align-items: center !important;
        gap: 0.65rem !important;
        border-radius: 999px !important;
        padding: 0 0.25rem 0 0 !important;
    }

    .pd-appshell-user-menu__avatar {
        width: 2.45rem !important;
        height: 2.45rem !important;
        border: 1px solid #99f6e4 !important;
        border-radius: 999px !important;
        background: #ecfeff !important;
        color: #0f766e !important;
        font-size: 0.78rem !important;
        font-weight: 900 !important;
        box-shadow: none !important;
    }

    .pd-appshell-user-menu__summary-text {
        display: grid !important;
        gap: 0.05rem !important;
        min-width: 0 !important;
    }

    .pd-appshell-user-menu__summary-name,
    .pd-appshell-user-menu__summary-role {
        max-width: 12rem !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .pd-appshell-user-menu__summary-name {
        color: #0f172a !important;
        font-size: 0.78rem !important;
        font-weight: 850 !important;
        line-height: 1.15 !important;
    }

    .pd-appshell-user-menu__summary-role {
        color: #64748b !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        line-height: 1.15 !important;
    }

    .pd-appshell-user-menu__panel,
    .pd-appshell-workspace-switcher__menu {
        border: 1px solid #dbe4ee !important;
        border-radius: 0.8rem !important;
        background: #ffffff !important;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16) !important;
    }

    .fi-main-sidebar {
        width: 17rem !important;
        background: #ffffff !important;
        border-inline-end: 1px solid #dbe4ee !important;
        box-shadow: 12px 0 30px rgba(15, 23, 42, 0.035) !important;
    }

    .fi-sidebar-header {
        min-height: 4.35rem !important;
        padding: 0 1rem !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    .admin-workspace-scope-wrap,
    .clinic-workspace-scope-wrap {
        margin: 0.9rem 0.75rem 1rem !important;
        border-color: #dbe4ee !important;
        border-radius: 0.9rem !important;
        background: #ffffff !important;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.045) !important;
    }

    .fi-sidebar-nav {
        padding: 0.9rem 0.85rem 1.35rem !important;
    }

    .fi-sidebar-group-label {
        color: #64748b !important;
        font-size: 0.74rem !important;
        font-weight: 850 !important;
        letter-spacing: 0 !important;
    }

    .fi-sidebar-item a {
        min-height: 2.5rem !important;
        border-radius: 0.7rem !important;
        color: #334155 !important;
        font-weight: 750 !important;
    }

    .fi-sidebar-item-active a,
    .fi-sidebar-item.fi-active a,
    .fi-sidebar-item[aria-current="page"] a {
        background: #ecfdf5 !important;
        color: #0f766e !important;
        box-shadow: inset 3px 0 0 #0f766e !important;
    }

    .fi-main-ctn {
        background: #f8fafc !important;
    }

    .fi-main {
        padding-inline: clamp(1.1rem, 2.2vw, 2rem) !important;
    }

    @media (max-width: 1180px) {
        .pd-appshell-global-header {
            grid-template-columns: minmax(12rem, 18rem) minmax(14rem, 1fr) max-content !important;
            column-gap: 1rem !important;
        }

        .pd-appshell-user-menu__summary-text,
        .pd-appshell-user-menu__chevron {
            display: none !important;
        }
    }

    @media (max-width: 860px) {
        .pd-appshell-global-header {
            grid-template-columns: minmax(0, 1fr) max-content !important;
        }

        .pd-appshell-global-header__search {
            display: none !important;
        }
    }

    /*
     * Navigation system alignment.
     * Primary movement belongs to the sidebar; the top bar stays global and light.
     */
    .fi-topbar:has(.pd-appshell-global-header) {
        min-height: 4rem !important;
        padding: 0 1.15rem !important;
        background: #ffffff !important;
        border-bottom: 1px solid #dbe4ee !important;
        box-shadow: none !important;
    }

    .pd-appshell-global-header {
        grid-template-columns: 18rem minmax(25rem, 38rem) max-content !important;
        column-gap: clamp(1rem, 4vw, 4.5rem) !important;
        min-height: 4rem !important;
    }

    .pd-appshell-global-header__left {
        gap: 0.85rem !important;
    }

    .pd-appshell-global-header__brand {
        gap: 0.65rem !important;
        font-size: 1.02rem !important;
    }

    .pd-appshell-global-header__logo {
        width: 2.2rem !important;
        height: 2.2rem !important;
        border-radius: 0.65rem !important;
    }

    .pd-appshell-workspace-switcher__summary,
    .pd-appshell-icon-button {
        min-height: 2.35rem !important;
        border-radius: 0.55rem !important;
    }

    .pd-appshell-workspace-switcher__summary {
        padding-inline: 0.85rem !important;
    }

    .pd-appshell-global-header__search {
        min-height: 2.35rem !important;
        border-radius: 0.55rem !important;
        background: #f8fafc !important;
    }

    .pd-appshell-icon-button {
        width: 2.35rem !important;
        height: 2.35rem !important;
    }

    .pd-appshell-user-menu__summary {
        min-height: 2.35rem !important;
    }

    .pd-appshell-user-menu__avatar {
        width: 2.35rem !important;
        height: 2.35rem !important;
    }

    .fi-main-sidebar {
        width: 17.5rem !important;
        min-width: 17.5rem !important;
        max-width: 17.5rem !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .fi-sidebar-header {
        min-height: 4rem !important;
        padding-inline: 1rem !important;
    }

    .app-sidebar-greeting {
        gap: 0.65rem !important;
    }

    .app-sidebar-greeting__avatar {
        width: 2.2rem !important;
        height: 2.2rem !important;
        border-radius: 999px !important;
        background: #ecfeff !important;
        border-color: #99f6e4 !important;
        color: #0f766e !important;
    }

    .app-sidebar-toggle-btn {
        width: 2.15rem !important;
        height: 2.15rem !important;
        border-radius: 0.65rem !important;
        box-shadow: none !important;
    }

    .admin-workspace-scope-wrap,
    .clinic-workspace-scope-wrap {
        margin: 0.75rem 0.75rem 1rem !important;
        box-shadow: none !important;
    }

    .fi-sidebar-nav {
        padding: 0.7rem 0.75rem 1.25rem !important;
    }

    .fi-sidebar-group {
        margin-block: 0.35rem !important;
    }

    .fi-sidebar-group-btn {
        padding-inline: 0.45rem !important;
    }

    .fi-sidebar-group-label {
        color: #667085 !important;
        font-size: 0.73rem !important;
        font-weight: 800 !important;
    }

    .fi-sidebar-item a {
        min-height: 2.35rem !important;
        border-radius: 0.55rem !important;
        padding-inline: 0.65rem !important;
        color: #344054 !important;
        font-size: 0.85rem !important;
        font-weight: 720 !important;
    }

    .fi-sidebar-item a:hover {
        background: #f2f4f7 !important;
        color: #101828 !important;
    }

    .fi-sidebar-item-active a,
    .fi-sidebar-item.fi-active a,
    .fi-sidebar-item[aria-current="page"] a {
        background: #e8f8f4 !important;
        color: #0f766e !important;
        box-shadow: inset 3px 0 0 #0f766e !important;
    }

    .fi-sidebar-item-icon,
    .fi-sidebar-group-icon {
        color: currentColor !important;
    }

    .fi-main {
        padding: 0 clamp(1.25rem, 2vw, 2rem) 1.5rem !important;
    }

    .fi-page-header-main-ctn {
        gap: 1.25rem !important;
        padding-top: 0 !important;
    }

    .fi-header {
        width: calc(100% + clamp(2.5rem, 4vw, 4rem)) !important;
        box-sizing: border-box !important;
        margin: 0 calc(-1 * clamp(1.25rem, 2vw, 2rem)) !important;
        border: 0 !important;
        border-bottom: 1px solid #e4e7ec !important;
        border-radius: 0 !important;
        background: #ffffff !important;
        box-shadow: none !important;
        padding: 1rem clamp(1.25rem, 2vw, 2rem) !important;
    }

    .fi-header-heading {
        color: #0f172a !important;
        font-size: clamp(1.55rem, 2vw, 1.9rem) !important;
        font-weight: 850 !important;
        letter-spacing: 0 !important;
    }

    .fi-header-subheading {
        color: #475569 !important;
    }

    .pd-hero-header__actions:not(:has(.fi-btn, a, button)) {
        display: none !important;
    }

    html.app-sidebar-collapsed .fi-main-sidebar {
        width: 4.25rem !important;
        min-width: 4.25rem !important;
        max-width: 4.25rem !important;
    }

    @media (max-width: 1180px) {
        .pd-appshell-global-header {
            grid-template-columns: minmax(11rem, 17rem) minmax(12rem, 1fr) max-content !important;
        }
    }

    /*
     * TailAdmin-style shared header final pass.
     * This is intentionally scoped to the shared AppShell header so SaaS,
     * Verification, Clinic, and DSO panels inherit one consistent top bar.
     */
    .fi-topbar:has(.pd-appshell-global-header) {
        position: sticky !important;
        top: 0 !important;
        z-index: 45 !important;
        height: 4rem !important;
        min-height: 4rem !important;
        padding: 0 1.5rem !important;
        border-bottom: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    }

    .fi-topbar:has(.pd-appshell-global-header) > nav,
    .fi-topbar:has(.pd-appshell-global-header) > div {
        width: 100% !important;
        max-width: none !important;
    }

    .pd-appshell-global-header {
        display: grid !important;
        width: 100% !important;
        height: 4rem !important;
        min-height: 4rem !important;
        grid-template-columns: minmax(15rem, 20rem) minmax(22rem, 36rem) max-content !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: clamp(1rem, 3vw, 3.5rem) !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .pd-appshell-global-header__left {
        display: inline-flex !important;
        min-width: 0 !important;
        align-items: center !important;
        gap: 1rem !important;
    }

    .pd-appshell-global-header__brand {
        display: inline-flex !important;
        min-width: 0 !important;
        align-items: center !important;
        gap: 0.7rem !important;
        color: #101828 !important;
        font-size: 1.05rem !important;
        font-weight: 850 !important;
        text-decoration: none !important;
        white-space: nowrap !important;
    }

    .pd-appshell-global-header__logo {
        display: inline-flex !important;
        width: 2.35rem !important;
        height: 2.35rem !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        justify-content: center !important;
        border: 0 !important;
        border-radius: 0.75rem !important;
        background: #0f766e !important;
        color: #ffffff !important;
        font-size: 0.75rem !important;
        font-weight: 900 !important;
        box-shadow: none !important;
    }

    .pd-appshell-global-header__brand-name {
        color: #101828 !important;
        font-size: 1.05rem !important;
        font-weight: 850 !important;
        line-height: 1 !important;
    }

    .pd-appshell-workspace-switcher {
        position: relative !important;
        flex: 0 0 auto !important;
    }

    .pd-appshell-workspace-switcher__summary {
        display: inline-flex !important;
        height: 2.5rem !important;
        min-height: 2.5rem !important;
        align-items: center !important;
        gap: 0.55rem !important;
        padding: 0 0.9rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.65rem !important;
        background: #ffffff !important;
        color: #344054 !important;
        font-size: 0.86rem !important;
        font-weight: 800 !important;
        box-shadow: none !important;
    }

    .pd-appshell-workspace-switcher__summary::-webkit-details-marker,
    .pd-appshell-user-menu__summary::-webkit-details-marker {
        display: none !important;
    }

    .pd-appshell-workspace-switcher__icon {
        width: 0.95rem !important;
        height: 0.95rem !important;
        color: #667085 !important;
    }

    .pd-appshell-global-header__search {
        display: inline-flex !important;
        width: min(100%, 36rem) !important;
        height: 2.5rem !important;
        min-height: 2.5rem !important;
        justify-self: center !important;
        align-items: center !important;
        gap: 0.65rem !important;
        padding: 0 0.75rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.7rem !important;
        background: #f8fafc !important;
        color: #98a2b3 !important;
        box-shadow: none !important;
    }

    .pd-appshell-global-header__search-icon {
        width: 1.05rem !important;
        height: 1.05rem !important;
        flex: 0 0 auto !important;
        color: #667085 !important;
    }

    .pd-appshell-global-header__search-text {
        min-width: 0 !important;
        flex: 1 1 auto !important;
        overflow: hidden !important;
        color: #98a2b3 !important;
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .pd-appshell-global-header__kbd {
        display: inline-flex !important;
        min-width: 3.1rem !important;
        height: 1.5rem !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.45rem !important;
        background: #ffffff !important;
        color: #475467 !important;
        font-size: 0.72rem !important;
        font-weight: 800 !important;
    }

    .pd-appshell-global-header__utilities {
        display: inline-flex !important;
        min-width: 0 !important;
        justify-self: end !important;
        align-items: center !important;
        gap: 0.6rem !important;
    }

    .pd-appshell-icon-button {
        display: inline-flex !important;
        width: 2.5rem !important;
        height: 2.5rem !important;
        min-height: 2.5rem !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.65rem !important;
        background: #ffffff !important;
        color: #344054 !important;
        box-shadow: none !important;
    }

    .pd-appshell-icon-button__icon {
        width: 1.1rem !important;
        height: 1.1rem !important;
    }

    .pd-appshell-user-menu {
        position: relative !important;
        margin-left: 0.45rem !important;
        padding-left: 0.9rem !important;
        border-left: 1px solid #e4e7ec !important;
    }

    .pd-appshell-user-menu__summary {
        display: inline-flex !important;
        height: 2.75rem !important;
        min-height: 2.75rem !important;
        align-items: center !important;
        gap: 0.65rem !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .pd-appshell-user-menu__avatar {
        display: inline-flex !important;
        width: 2.45rem !important;
        height: 2.45rem !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #99f6e4 !important;
        border-radius: 999px !important;
        background: #ecfeff !important;
        color: #0f766e !important;
        font-size: 0.78rem !important;
        font-weight: 900 !important;
    }

    .pd-appshell-user-menu__summary-text {
        display: grid !important;
        min-width: 0 !important;
        gap: 0.05rem !important;
    }

    .pd-appshell-user-menu__summary-name {
        max-width: 9.5rem !important;
        overflow: hidden !important;
        color: #101828 !important;
        font-size: 0.84rem !important;
        font-weight: 850 !important;
        line-height: 1.1 !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .pd-appshell-user-menu__summary-role {
        max-width: 9.5rem !important;
        overflow: hidden !important;
        color: #667085 !important;
        font-size: 0.72rem !important;
        font-weight: 700 !important;
        line-height: 1.1 !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .pd-appshell-user-menu__chevron {
        width: 0.95rem !important;
        height: 0.95rem !important;
        flex: 0 0 auto !important;
        color: #667085 !important;
    }

    .pd-appshell-workspace-switcher__menu,
    .pd-appshell-user-menu__panel {
        margin-top: 0.6rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.75rem !important;
        background: #ffffff !important;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.14) !important;
    }

    @media (max-width: 1180px) {
        .pd-appshell-global-header {
            grid-template-columns: minmax(13rem, 18rem) minmax(14rem, 1fr) max-content !important;
            gap: 1rem !important;
        }

        .pd-appshell-user-menu__summary-text,
        .pd-appshell-user-menu__chevron {
            display: none !important;
        }
    }

    @media (max-width: 860px) {
        .fi-topbar:has(.pd-appshell-global-header) {
            padding-inline: 1rem !important;
        }

        .pd-appshell-global-header {
            grid-template-columns: minmax(0, 1fr) max-content !important;
        }

        .pd-appshell-global-header__search {
            display: none !important;
        }

        .pd-appshell-global-header__brand-name {
            display: none !important;
        }
    }

    /*
     * Workspace-frame alignment.
     * The sidebar owns the left navigation column. The global header starts
     * beside it and belongs to the workspace, matching the product frame.
     */
    @media (min-width: 1024px) {
        .fi-main-sidebar {
            top: 0 !important;
            z-index: 55 !important;
            height: 100vh !important;
            border-inline-end: 1px solid #dbe4ee !important;
            background: #ffffff !important;
        }

        .fi-topbar:has(.pd-appshell-global-header) {
            margin-inline-start: 17.5rem !important;
            width: calc(100% - 17.5rem) !important;
            padding-inline: 1.5rem !important;
        }

        html.app-sidebar-collapsed .fi-topbar:has(.pd-appshell-global-header) {
            margin-inline-start: 4.25rem !important;
            width: calc(100% - 4.25rem) !important;
        }

        .pd-appshell-global-header {
            grid-template-columns: max-content minmax(22rem, 42rem) max-content !important;
            gap: clamp(1rem, 3vw, 3rem) !important;
        }

        .pd-appshell-global-header__brand {
            display: none !important;
        }

        .pd-appshell-global-header__left {
            gap: 0 !important;
        }

        .pd-appshell-workspace-switcher__summary {
            min-width: 9.5rem !important;
            justify-content: space-between !important;
        }
    }

    @media (max-width: 1023px) {
        .pd-appshell-global-header__brand {
            display: inline-flex !important;
        }
    }

    /*
     * Sidebar brand area.
     * Anchors the navigation at the top with only the product identity.
     */
    .fi-sidebar-header {
        height: 4rem !important;
        min-height: 4rem !important;
        align-items: center !important;
        padding: 0.75rem 1rem !important;
    }

    .fi-sidebar-header-logo-ctn {
        display: none !important;
    }

    .app-sidebar-greeting {
        display: inline-flex !important;
        align-items: center !important;
        flex: 1 1 auto !important;
        min-width: 0 !important;
        gap: 0.7rem !important;
    }

    .app-sidebar-brand {
        display: inline-flex !important;
        min-width: 0 !important;
        align-items: center !important;
        gap: 0.7rem !important;
        color: #101828 !important;
        text-decoration: none !important;
    }

    .app-sidebar-brand__mark {
        display: inline-flex !important;
        width: 2.35rem !important;
        height: 2.35rem !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 0.75rem !important;
        background: #0f766e !important;
        color: #ffffff !important;
        font-size: 0.76rem !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
    }

    .app-sidebar-brand__name {
        overflow: hidden !important;
        color: #101828 !important;
        font-size: 1.05rem !important;
        font-weight: 850 !important;
        letter-spacing: 0 !important;
        line-height: 1 !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .app-sidebar-header-toggle {
        flex: 0 0 auto !important;
    }

    .fi-sidebar-footer {
        margin-top: auto !important;
        padding: 0.85rem 1rem 1rem !important;
        border-top: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
    }

    .app-sidebar-user-footer {
        display: flex !important;
        min-width: 0 !important;
        align-items: center !important;
        gap: 0.55rem !important;
        padding: 0.65rem !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.85rem !important;
        background: #ffffff !important;
    }

    .app-sidebar-user-footer__identity {
        display: flex !important;
        min-width: 0 !important;
        flex: 1 1 auto !important;
        align-items: center !important;
        gap: 0.7rem !important;
    }

    .app-sidebar-user-footer__avatar {
        display: inline-flex !important;
        width: 2.25rem !important;
        height: 2.25rem !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #99f6e4 !important;
        border-radius: 999px !important;
        background: #ecfeff !important;
        color: #0f766e !important;
        font-size: 0.78rem !important;
        font-weight: 900 !important;
    }

    .app-sidebar-user-footer__body {
        display: grid !important;
        min-width: 0 !important;
        gap: 0.08rem !important;
    }

    .app-sidebar-user-footer__name {
        overflow: hidden !important;
        color: #101828 !important;
        font-size: 0.9rem !important;
        font-weight: 850 !important;
        line-height: 1.15 !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .app-sidebar-user-footer__role {
        overflow: hidden !important;
        color: #667085 !important;
        font-size: 0.73rem !important;
        font-weight: 700 !important;
        line-height: 1.15 !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .app-sidebar-user-footer__actions {
        display: inline-flex !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        gap: 0.35rem !important;
    }

    .app-sidebar-user-footer__action {
        display: inline-flex !important;
        width: 2rem !important;
        height: 2rem !important;
        flex: 0 0 2rem !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border: 1px solid #d8e1ea !important;
        border-radius: 0.55rem !important;
        background: #ffffff !important;
        color: #344054 !important;
        text-decoration: none !important;
        transition: border-color 140ms ease, background-color 140ms ease, color 140ms ease !important;
    }

    .app-sidebar-user-footer__action:hover {
        border-color: #99d5ce !important;
        background: #f0fdfa !important;
        color: #0f766e !important;
    }

    .app-sidebar-user-footer__action--logout:hover {
        border-color: #fecaca !important;
        background: #fff7f7 !important;
        color: #b42318 !important;
    }

    .app-sidebar-user-footer__action-icon {
        width: 1.05rem !important;
        height: 1.05rem !important;
        flex: 0 0 auto !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-header {
        height: 4rem !important;
        min-height: 4rem !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0.75rem 0.45rem !important;
    }

    html.app-sidebar-collapsed .app-sidebar-greeting {
        display: none !important;
    }

    html.app-sidebar-collapsed .app-sidebar-header-toggle {
        margin-top: 0 !important;
    }

    /*
     * Collapsed sidebar icon rail.
     * Keep navigation links clickable while removing only the text chrome.
     */
    html.app-sidebar-collapsed .fi-sidebar-nav,
    html.app-sidebar-collapsed .fi-sidebar-nav-groups,
    html.app-sidebar-collapsed .fi-sidebar-group,
    html.app-sidebar-collapsed .fi-sidebar-group-items {
        display: flex !important;
        width: 100% !important;
        max-width: none !important;
        opacity: 1 !important;
        visibility: visible !important;
        overflow: visible !important;
        pointer-events: auto !important;
        transform: none !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-nav {
        align-items: center !important;
        padding: 0.75rem 0.5rem 0.9rem !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-nav-groups,
    html.app-sidebar-collapsed .fi-sidebar-group,
    html.app-sidebar-collapsed .fi-sidebar-group-items {
        flex-direction: column !important;
        align-items: center !important;
        gap: 0.35rem !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-group-btn,
    html.app-sidebar-collapsed .fi-sidebar-group-label,
    html.app-sidebar-collapsed .fi-sidebar-group-collapse-btn,
    html.app-sidebar-collapsed .fi-sidebar-item-label,
    html.app-sidebar-collapsed .fi-sidebar-item-badge-ctn,
    html.app-sidebar-collapsed .fi-sidebar-item-grouped-border,
    html.app-sidebar-collapsed .admin-workspace-scope-wrap,
    html.app-sidebar-collapsed .clinic-workspace-scope-wrap,
    html.app-sidebar-collapsed .app-sidebar-user-footer__body,
    html.app-sidebar-collapsed .app-sidebar-user-footer__actions {
        display: none !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-item,
    html.app-sidebar-collapsed .fi-sidebar-item-has-url {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-item-btn {
        display: inline-flex !important;
        width: 2.75rem !important;
        height: 2.65rem !important;
        min-height: 2.65rem !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border-radius: 0.8rem !important;
        color: #344054 !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-item-btn:hover {
        background: #f2f4f7 !important;
        color: #101828 !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-item-active .fi-sidebar-item-btn,
    html.app-sidebar-collapsed .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
    html.app-sidebar-collapsed .fi-sidebar-item[aria-current="page"] .fi-sidebar-item-btn {
        background: #e8f8f4 !important;
        color: #0f766e !important;
        box-shadow: inset 3px 0 0 #0f766e !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-item-icon {
        width: 1.25rem !important;
        height: 1.25rem !important;
        margin: 0 !important;
        color: currentColor !important;
    }

    html.app-sidebar-collapsed .fi-sidebar-footer {
        display: flex !important;
        width: 100% !important;
        justify-content: center !important;
        padding: 0.75rem 0 0.9rem !important;
        opacity: 1 !important;
        visibility: visible !important;
        max-width: none !important;
        overflow: visible !important;
        pointer-events: auto !important;
        transform: none !important;
    }

    html.app-sidebar-collapsed .app-sidebar-user-footer {
        width: 2.5rem !important;
        height: 2.5rem !important;
        display: flex !important;
        justify-content: center !important;
        gap: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    html.app-sidebar-collapsed .app-sidebar-user-footer__identity {
        justify-content: center !important;
        gap: 0 !important;
    }

    /*
     * Full-height sidebar correction.
     * Filament offsets the sidebar below the topbar by default. Our product
     * frame keeps navigation full height and shifts the workspace beside it.
     */
    @media (min-width: 1024px) {
        html {
            --pd-app-sidebar-width: 17.5rem;
        }

        html.app-sidebar-collapsed {
            --pd-app-sidebar-width: 4.25rem;
        }

        /*
         * Render-safe desktop shell.
         * Filament cloaks the sidebar and main region until Alpine restores
         * its persisted state. Keep both regions visible during that window
         * so a refresh never leaves an empty navigation column or page body.
         */
        .fi-main-sidebar[x-cloak],
        .fi-main-sidebar[x-cloak="-lg"] {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
        }

        .fi-main-ctn {
            display: flex !important;
            opacity: 1 !important;
        }

        .fi-body-has-topbar .fi-layout {
            margin-top: -4rem !important;
        }

        .fi-main-sidebar {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            inset-inline-start: 0 !important;
            inset-inline-end: auto !important;
            width: var(--pd-app-sidebar-width) !important;
            min-width: var(--pd-app-sidebar-width) !important;
            max-width: var(--pd-app-sidebar-width) !important;
            height: 100vh !important;
            height: 100dvh !important;
            max-height: 100vh !important;
            max-height: 100dvh !important;
            overflow: hidden !important;
            transform: translateX(0) !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .fi-sidebar {
            top: 0 !important;
            bottom: 0 !important;
            width: var(--pd-app-sidebar-width) !important;
            min-width: var(--pd-app-sidebar-width) !important;
            max-width: var(--pd-app-sidebar-width) !important;
            height: 100vh !important;
            height: 100dvh !important;
            max-height: 100vh !important;
            max-height: 100dvh !important;
        }

        .fi-sidebar {
            z-index: 60 !important;
        }

        .fi-main-sidebar {
            padding-top: 0 !important;
        }

        .fi-sidebar-header,
        .admin-workspace-scope-wrap,
        .clinic-workspace-scope-wrap,
        .fi-sidebar-footer {
            flex: 0 0 auto !important;
        }

        .fi-sidebar-nav {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            overscroll-behavior: contain !important;
        }

        .fi-layout,
        .fi-main-ctn {
            min-height: 100vh !important;
        }

        .fi-main-ctn {
            width: calc(100% - var(--pd-app-sidebar-width)) !important;
            margin-inline-start: var(--pd-app-sidebar-width) !important;
            padding-inline-start: 0 !important;
            transition: width 0.24s ease, margin-inline-start 0.24s ease !important;
        }

        .fi-topbar:has(.pd-appshell-global-header) {
            width: calc(100% - var(--pd-app-sidebar-width)) !important;
            margin-inline-start: var(--pd-app-sidebar-width) !important;
        }

        .fi-body-has-topbar .fi-main-ctn {
            padding-top: 4rem !important;
        }

        .fi-main {
            padding-top: 0 !important;
        }
    }

    /*
     * Approved compact clinic scope selector.
     * Matches public/samples/clinic-scope-sidebar-preview.html.
     */
    .admin-workspace-scope-wrap,
    .clinic-workspace-scope-wrap {
        margin: 1rem !important;
        padding: 0 0 1rem !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .admin-workspace-scope-wrap::after,
    .clinic-workspace-scope-wrap::after {
        inset-inline: 0 !important;
        bottom: 0 !important;
        height: 1px !important;
        background: #e2e8f0 !important;
    }

    .admin-workspace-scope,
    .clinic-workspace-scope {
        width: 100% !important;
        margin: 0 !important;
        padding: 0.75rem !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.75rem !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .admin-workspace-scope__eyebrow,
    .clinic-workspace-scope__eyebrow {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin: 0 0 0.5rem !important;
        color: #0f766e !important;
        font-size: 0.68rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.08em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    .admin-workspace-scope__eyebrow::before,
    .clinic-workspace-scope__eyebrow::before {
        width: 0.5rem !important;
        height: 0.5rem !important;
        border-radius: 999px !important;
        background: #0f766e !important;
        box-shadow: 0 0 0 4px #e8f8f4 !important;
    }

    .admin-workspace-scope__hint,
    .clinic-workspace-scope__hint {
        margin: 0 0 0.65rem !important;
        color: #667085 !important;
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        line-height: 1.45 !important;
    }

    .admin-workspace-scope__field-label {
        display: block !important;
        margin: 0 0 0.35rem !important;
        color: #344054 !important;
        font-size: 0.68rem !important;
        font-weight: 850 !important;
        letter-spacing: 0 !important;
        line-height: 1.2 !important;
    }

    .clinic-workspace-scope-wrap__section,
    .clinic-workspace-scope__title {
        display: none !important;
    }

    .admin-workspace-scope__select,
    .clinic-workspace-scope__select {
        width: 100% !important;
        height: 2.5rem !important;
        min-height: 2.5rem !important;
        border: 1px solid #cfd8e3 !important;
        border-radius: 0.65rem !important;
        background: #ffffff !important;
        color: #101828 !important;
        padding: 0 0.65rem !important;
        font-size: 0.8rem !important;
        font-weight: 750 !important;
        line-height: 1 !important;
        box-shadow: none !important;
    }

    .admin-workspace-scope__status,
    .clinic-workspace-scope__status {
        margin-top: 0.55rem !important;
        color: #667085 !important;
        font-size: 0.72rem !important;
        font-weight: 650 !important;
        line-height: 1.35 !important;
    }

    .admin-workspace-scope__status strong,
    .clinic-workspace-scope__status strong {
        color: #101828 !important;
        font-weight: 850 !important;
    }

    html.app-sidebar-collapsed .admin-workspace-scope-wrap,
    html.app-sidebar-collapsed .clinic-workspace-scope-wrap {
        display: none !important;
    }

    /*
     * Verification Requests approved queue layout.
     * Makes the real page match the mockup more closely: contained header,
     * clean actions, compact status chips, and tighter table spacing.
     */
    .fi-resource-verifications .fi-main {
        padding-top: 1.05rem !important;
    }

    .fi-resource-verifications .fi-header {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        grid-template-areas: "copy actions" !important;
        align-items: center !important;
        column-gap: 1.25rem !important;
        row-gap: 0.65rem !important;
        min-height: 0 !important;
        margin: 0 0 1rem !important;
        padding: 1.25rem 1.45rem !important;
        border: 1px solid #d9e5ef !important;
        border-radius: 1rem !important;
        background: #ffffff !important;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08) !important;
    }

    .fi-resource-verifications .fi-header > div:first-child {
        display: flex !important;
        flex-direction: column !important;
        grid-area: copy !important;
        min-width: 0 !important;
    }

    .fi-resource-verifications .fi-header-actions-ctn {
        grid-area: actions !important;
        align-self: center !important;
        justify-self: end !important;
        margin: 0 !important;
    }

    .fi-resource-verifications .fi-header-heading {
        order: 1 !important;
        color: #0f172a !important;
        font-size: clamp(1.72rem, 2.1vw, 2.05rem) !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        line-height: 1.05 !important;
    }

    .fi-resource-verifications .fi-header-subheading {
        order: 2 !important;
        max-width: 42rem !important;
        margin-top: 0.28rem !important;
        color: #64748b !important;
        font-size: 0.88rem !important;
        line-height: 1.45 !important;
    }

    .fi-resource-verifications .pd-appshell-workspace-header {
        display: none !important;
    }

    .fi-resource-verifications .fi-breadcrumbs,
    .fi-resource-verifications .fi-header .fi-breadcrumbs {
        order: 3 !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.45rem !important;
        margin-top: 0.72rem !important;
    }

    .fi-resource-verifications .fi-breadcrumbs::before {
        content: 'Verification Workspace';
        display: inline-flex !important;
        min-height: 1.55rem !important;
        align-items: center !important;
        border: 1px solid rgba(15, 118, 110, 0.22) !important;
        border-radius: 999px !important;
        background: rgba(15, 118, 110, 0.08) !important;
        padding: 0 0.55rem !important;
        color: #0f766e !important;
        font-size: 0.72rem !important;
        font-weight: 850 !important;
        line-height: 1 !important;
        white-space: nowrap !important;
    }

    .fi-resource-verifications .fi-breadcrumbs ol,
    .fi-resource-verifications .fi-breadcrumbs-list {
        min-height: 2rem !important;
        padding: 0.28rem 0.75rem !important;
        border: 1px solid #b9d7ff !important;
        border-radius: 999px !important;
        background: #f8fbff !important;
    }

    .fi-resource-verifications .fi-breadcrumbs li,
    .fi-resource-verifications .fi-breadcrumbs a,
    .fi-resource-verifications .fi-breadcrumbs span {
        color: #475569 !important;
        font-size: 0.78rem !important;
        font-weight: 650 !important;
        line-height: 1 !important;
    }

    .fi-resource-verifications .fi-breadcrumbs svg {
        width: 0.85rem !important;
        height: 0.85rem !important;
        color: #94a3b8 !important;
    }

    .fi-resource-verifications .fi-header-actions,
    .fi-resource-verifications .fi-ac {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 0.65rem !important;
        flex-wrap: wrap !important;
    }

    .fi-resource-verifications .fi-header-actions .fi-btn,
    .fi-resource-verifications .fi-ac .fi-btn {
        min-height: 2.45rem !important;
        border-radius: 0.75rem !important;
        box-shadow: none !important;
        font-size: 0.82rem !important;
        font-weight: 850 !important;
    }

    .fi-resource-verifications .fi-ta {
        position: relative !important;
        border-radius: 1rem !important;
        border: 1px solid #d9e5ef !important;
        background: #ffffff !important;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07) !important;
        overflow: visible !important;
    }

    .fi-resource-verifications .fi-ta-header {
        position: relative !important;
        top: auto !important;
        z-index: 8 !important;
        min-height: 3.25rem !important;
        height: auto !important;
        display: flex !important;
        align-items: center !important;
        padding: 0.65rem 0.95rem !important;
        background: #ffffff !important;
        border-bottom: 0 !important;
        backdrop-filter: none !important;
    }

    .fi-resource-verifications .verification-queue-kpis {
        position: static !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 0.42rem !important;
        padding: 0 !important;
        border-bottom: 0 !important;
        background: #ffffff !important;
    }

    .fi-resource-verifications .verification-queue-kpi {
        display: inline-flex !important;
        min-height: 1.8rem !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 0.75rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 999px !important;
        background: #ffffff !important;
        color: #334155 !important;
        font-size: 0.76rem !important;
        font-weight: 850 !important;
        line-height: 1 !important;
        white-space: nowrap !important;
    }

    .fi-resource-verifications .verification-queue-kpi--all {
        border-color: rgba(15, 118, 110, 0.28) !important;
        background: #f0fdfa !important;
        color: #0f766e !important;
    }

    .fi-resource-verifications .verification-queue-kpi--pending,
    .fi-resource-verifications .verification-queue-kpi--waiting {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
        color: #b45309 !important;
    }

    .fi-resource-verifications .verification-queue-kpi--progress {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
        color: #2563eb !important;
    }

    .fi-resource-verifications .verification-queue-kpi--complete {
        border-color: #bbf7d0 !important;
        background: #ecfdf5 !important;
        color: #15803d !important;
    }

    .fi-resource-verifications .fi-ta-toolbar {
        position: relative !important;
        top: auto !important;
        right: auto !important;
        z-index: 5 !important;
        width: 100% !important;
        min-height: 3.25rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        margin-top: -3.25rem !important;
        padding: 0.52rem 0.95rem 0.52rem 28rem !important;
        border-bottom: 1px solid #e3edf5 !important;
        background: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta-header > div:not(:has(.verification-queue-kpis)) {
        position: absolute !important;
        top: 0.62rem !important;
        right: 0.95rem !important;
        z-index: 7 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.45rem !important;
    }

    .fi-resource-verifications .fi-ta-search-field input,
    .fi-resource-verifications .fi-ta-toolbar input {
        min-height: 2.15rem !important;
        border-radius: 0.65rem !important;
        font-size: 0.8rem !important;
    }

    .fi-resource-verifications .fi-ta-toolbar .fi-icon-btn,
    .fi-resource-verifications .fi-ta-header .fi-icon-btn,
    .fi-resource-verifications .fi-ta-filters button,
    .fi-resource-verifications .fi-ta-filter-trigger,
    .fi-resource-verifications .fi-ta-column-manager-trigger {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 2.2rem !important;
        height: 2.2rem !important;
        border: 1px solid #dbe4ee !important;
        border-radius: 0.65rem !important;
        background: #ffffff !important;
        color: #475569 !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-ta-toolbar .fi-icon-btn:hover,
    .fi-resource-verifications .fi-ta-header .fi-icon-btn:hover,
    .fi-resource-verifications .fi-ta-filters button:hover,
    .fi-resource-verifications .fi-ta-filter-trigger:hover,
    .fi-resource-verifications .fi-ta-column-manager-trigger:hover {
        border-color: rgba(15, 118, 110, 0.3) !important;
        background: #f0fdfa !important;
        color: #0f766e !important;
    }

    .fi-resource-verifications .fi-ta-toolbar .fi-icon-btn svg,
    .fi-resource-verifications .fi-ta-header .fi-icon-btn svg,
    .fi-resource-verifications .fi-ta-filters button svg {
        width: 1.05rem !important;
        height: 1.05rem !important;
    }

    .fi-resource-verifications .fi-ta-filters {
        top: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    .fi-resource-verifications .fi-ta-content {
        overflow-x: auto !important;
        border-bottom-left-radius: 1rem !important;
        border-bottom-right-radius: 1rem !important;
    }

    @media (max-width: 1100px) {
        .fi-resource-verifications .fi-ta-header {
            min-height: auto !important;
            height: auto !important;
            padding: 0.7rem 0.95rem 0 !important;
            align-items: flex-start !important;
        }

        .fi-resource-verifications .verification-queue-kpis,
        .fi-resource-verifications .fi-ta-header > div:not(:has(.verification-queue-kpis)) {
            position: static !important;
            width: 100% !important;
            justify-content: flex-start !important;
            margin-top: 0.45rem !important;
        }

        .fi-resource-verifications .fi-ta-toolbar {
            margin-top: 0 !important;
            padding: 0.7rem 0.95rem !important;
            justify-content: flex-start !important;
        }
    }

    .fi-resource-verifications .fi-ta-row {
        min-height: 3.95rem !important;
    }

    .fi-resource-verifications .fi-ta-cell,
    .fi-resource-verifications .fi-ta-header-cell {
        padding-block: 0.54rem !important;
    }

    .fi-resource-verifications .fi-ta-header-cell,
    .fi-resource-verifications .fi-ta-header-cell button,
    .fi-resource-verifications .fi-ta-header-cell span {
        color: #0f172a !important;
        font-size: 0.78rem !important;
        font-weight: 850 !important;
        letter-spacing: 0 !important;
    }

    .fi-resource-verifications .fi-ta-cell .fi-ta-text,
    .fi-resource-verifications .fi-ta-cell span,
    .fi-resource-verifications .fi-ta-cell div {
        line-height: 1.25 !important;
    }

    .fi-resource-verifications .fi-ta-cell .fi-ta-text-item-label,
    .fi-resource-verifications .fi-ta-cell .fi-ta-text,
    .fi-resource-verifications .fi-ta-cell > div:not(.fi-ta-actions) {
        color: #0f172a !important;
        font-weight: 760 !important;
    }

    .fi-resource-verifications .fi-ta-cell .fi-ta-text-item-description,
    .fi-resource-verifications .fi-ta-cell .fi-ta-text-description {
        color: #64748b !important;
        font-weight: 650 !important;
    }

    .fi-resource-verifications .fi-ta-actions {
        gap: 0.4rem !important;
    }

    .fi-resource-verifications .fi-ta-actions .fi-btn,
    .fi-resource-verifications .fi-ta-actions button {
        min-height: 2rem !important;
        border-radius: 0.62rem !important;
        border-color: #dbe4ee !important;
        background: #ffffff !important;
        color: #334155 !important;
        font-size: 0.76rem !important;
        font-weight: 800 !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-ta-actions .fi-btn:hover,
    .fi-resource-verifications .fi-ta-actions button:hover {
        background: #f8fafc !important;
        color: #0f766e !important;
    }

    .fi-resource-verifications .fi-badge {
        border-radius: 999px !important;
        font-weight: 800 !important;
    }

    @media (max-width: 900px) {
        .fi-resource-verifications .fi-header {
            display: flex !important;
            align-items: flex-start !important;
            flex-direction: column !important;
            min-height: 0 !important;
            padding: 1.15rem !important;
        }

        .fi-resource-verifications .fi-header-actions,
        .fi-resource-verifications .fi-ac {
            width: 100% !important;
            justify-content: flex-start !important;
        }
    }

    /* Approved Verification Requests Option A: operational table. */
    .fi-resource-verifications .fi-main {
        padding-top: 1.5rem !important;
    }

    .fi-resource-verifications .fi-header {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        grid-template-areas: "copy actions" !important;
        align-items: end !important;
        gap: 1rem !important;
        margin: 0 0 1.35rem !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-header-heading {
        color: #101828 !important;
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        line-height: 1.2 !important;
    }

    .fi-resource-verifications .fi-header-subheading {
        max-width: none !important;
        margin-top: 0.3rem !important;
        color: #667085 !important;
        font-size: 0.875rem !important;
        line-height: 1.4 !important;
    }

    .fi-resource-verifications .fi-breadcrumbs,
    .fi-resource-verifications .fi-header .fi-breadcrumbs {
        display: none !important;
    }

    .fi-resource-verifications .fi-header-actions,
    .fi-resource-verifications .fi-ac {
        gap: 0.5rem !important;
    }

    .fi-resource-verifications .fi-header-actions .fi-btn,
    .fi-resource-verifications .fi-ac .fi-btn {
        min-height: 2.35rem !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 0.45rem !important;
        background: #ffffff !important;
        color: #344054 !important;
        padding-inline: 0.85rem !important;
        font-size: 0.8rem !important;
        font-weight: 750 !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-header-actions .fi-color-primary,
    .fi-resource-verifications .fi-ac .fi-color-primary {
        border-color: #0f766e !important;
        background: #0f766e !important;
        color: #ffffff !important;
    }

    .fi-resource-verifications .fi-header-actions .fi-color-primary:hover,
    .fi-resource-verifications .fi-ac .fi-color-primary:hover {
        border-color: #0d6963 !important;
        background: #0d6963 !important;
        color: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta {
        border: 1px solid #dce3e8 !important;
        border-radius: 0.5rem !important;
        background: #ffffff !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    .fi-resource-verifications .fi-ta-header {
        display: block !important;
        min-height: 0 !important;
        padding: 0 1rem !important;
        border-bottom: 1px solid #e4e9ed !important;
        background: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta-header > div:not(:has(.verification-queue-kpis)) {
        position: static !important;
    }

    .fi-resource-verifications .verification-queue-kpis {
        display: flex !important;
        align-items: stretch !important;
        flex-wrap: nowrap !important;
        gap: 1.35rem !important;
        overflow-x: auto !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        scrollbar-width: thin !important;
    }

    .fi-resource-verifications .verification-queue-kpi {
        display: inline-flex !important;
        min-height: 3rem !important;
        align-items: center !important;
        flex: 0 0 auto !important;
        border: 0 !important;
        border-bottom: 2px solid transparent !important;
        border-radius: 0 !important;
        background: transparent !important;
        color: #667085 !important;
        padding: 0.15rem 0 !important;
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        text-decoration: none !important;
    }

    .fi-resource-verifications .verification-queue-kpi:hover {
        color: #0f766e !important;
    }

    .fi-resource-verifications .verification-queue-kpi.is-active {
        border-bottom-color: #0f8a82 !important;
        color: #0f766e !important;
    }

    .fi-resource-verifications .fi-ta-toolbar {
        position: static !important;
        width: 100% !important;
        min-height: 3.65rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 0.65rem !important;
        margin: 0 !important;
        padding: 0.7rem 1rem !important;
        border-bottom: 1px solid #e4e9ed !important;
        background: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta-search-field {
        width: min(21rem, 100%) !important;
        margin-right: auto !important;
    }

    .fi-resource-verifications .fi-ta-search-field input,
    .fi-resource-verifications .fi-ta-toolbar input {
        min-height: 2.3rem !important;
        border-radius: 0.4rem !important;
        font-size: 0.8rem !important;
    }

    .fi-resource-verifications .fi-ta-toolbar .fi-icon-btn,
    .fi-resource-verifications .fi-ta-header .fi-icon-btn,
    .fi-resource-verifications .fi-ta-filters button,
    .fi-resource-verifications .fi-ta-filter-trigger,
    .fi-resource-verifications .fi-ta-column-manager-trigger {
        width: 2.3rem !important;
        height: 2.3rem !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 0.4rem !important;
        background: #ffffff !important;
        color: #475467 !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-ta-filters {
        padding: 0.85rem 1rem !important;
        border-bottom: 1px solid #e4e9ed !important;
        background: #f8fafb !important;
    }

    .fi-resource-verifications .fi-ta-content {
        overflow-x: auto !important;
        border-radius: 0 !important;
    }

    .fi-resource-verifications .fi-ta-row {
        min-height: 4.25rem !important;
        background: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta-row:hover {
        background: #f8fbfb !important;
    }

    .fi-resource-verifications .fi-ta-cell,
    .fi-resource-verifications .fi-ta-header-cell {
        padding: 0.68rem 0.75rem !important;
        border-color: #e7ecef !important;
    }

    .fi-resource-verifications .fi-ta-header-cell,
    .fi-resource-verifications .fi-ta-header-cell button,
    .fi-resource-verifications .fi-ta-header-cell span {
        color: #475467 !important;
        font-size: 0.76rem !important;
        font-weight: 750 !important;
    }

    .fi-resource-verifications .pd-verification-primary-cell {
        display: flex !important;
        min-width: 8.75rem !important;
        flex-direction: column !important;
        gap: 0.2rem !important;
    }

    .fi-resource-verifications .pd-verification-primary-cell strong,
    .fi-resource-verifications .pd-verification-sla strong {
        color: #101828 !important;
        font-size: 0.8rem !important;
        font-weight: 750 !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }

    .fi-resource-verifications .pd-verification-primary-cell > span,
    .fi-resource-verifications .pd-verification-sla > span {
        color: #667085 !important;
        font-size: 0.72rem !important;
        font-weight: 550 !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }

    .fi-resource-verifications .pd-verification-sla {
        display: flex !important;
        min-width: 9.5rem !important;
        flex-direction: column !important;
        gap: 0.2rem !important;
    }

    .fi-resource-verifications .pd-verification-sla--overdue > span {
        color: #b42318 !important;
    }

    .fi-resource-verifications .pd-verification-sla--due_today > span,
    .fi-resource-verifications .pd-verification-sla--paused_waiting_clinic > span {
        color: #b54708 !important;
    }

    .fi-resource-verifications .pd-verification-assignee {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.45rem !important;
        color: #344054 !important;
        font-size: 0.76rem !important;
        font-weight: 650 !important;
        white-space: nowrap !important;
    }

    .fi-resource-verifications .pd-verification-assignee i {
        display: inline-grid !important;
        width: 1.65rem !important;
        height: 1.65rem !important;
        place-items: center !important;
        border-radius: 999px !important;
        background: #e4f4f2 !important;
        color: #0f766e !important;
        font-size: 0.66rem !important;
        font-style: normal !important;
        font-weight: 800 !important;
    }

    .fi-resource-verifications .fi-badge {
        border-radius: 999px !important;
        padding: 0.22rem 0.5rem !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
    }

    .fi-resource-verifications .fi-ta-actions {
        gap: 0.2rem !important;
        white-space: nowrap !important;
    }

    .fi-resource-verifications .fi-ta-actions .fi-btn {
        min-height: 2rem !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 0.4rem !important;
        background: #ffffff !important;
        color: #344054 !important;
        padding-inline: 0.65rem !important;
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-ta-actions .fi-icon-btn {
        width: 2rem !important;
        height: 2rem !important;
        border: 0 !important;
        background: transparent !important;
        color: #667085 !important;
    }

    @media (max-width: 900px) {
        .fi-resource-verifications .fi-header {
            display: flex !important;
            align-items: flex-start !important;
            flex-direction: column !important;
        }

        .fi-resource-verifications .fi-header-actions,
        .fi-resource-verifications .fi-ac {
            width: 100% !important;
            justify-content: flex-start !important;
        }

        .fi-resource-verifications .fi-ta-toolbar {
            align-items: stretch !important;
            flex-wrap: wrap !important;
        }

        .fi-resource-verifications .fi-ta-search-field {
            width: 100% !important;
        }
    }

    body:has(.fi-resource-verifications) .fi-dropdown-panel,
    body:has(.fi-resource-verifications) .pd-verification-column-panel {
        width: min(22rem, calc(100vw - 2rem)) !important;
        max-height: min(46vh, 21rem) !important;
        overflow: hidden !important;
        overscroll-behavior: contain !important;
        border-radius: 0.9rem !important;
        box-shadow: 0 22px 44px rgba(15, 23, 42, 0.16) !important;
    }

    body:has(.fi-resource-verifications) .pd-column-menu-content {
        display: flex !important;
        flex-direction: column !important;
        max-height: min(46vh, 21rem) !important;
        overflow: hidden !important;
    }

    body:has(.fi-resource-verifications) .pd-column-menu-list {
        flex: 1 1 auto !important;
        max-height: calc(min(46vh, 21rem) - 3.75rem) !important;
        overflow-y: auto !important;
        padding: 0.25rem 0.3rem 0.35rem 0 !important;
        overscroll-behavior: contain !important;
    }

    body:has(.fi-resource-verifications) .pd-column-menu-list::-webkit-scrollbar {
        width: 0.45rem !important;
    }

    body:has(.fi-resource-verifications) .pd-column-menu-list::-webkit-scrollbar-thumb {
        border-radius: 999px !important;
        background: #cbd5e1 !important;
    }

    body:has(.fi-resource-verifications) .pd-verification-column-panel .fi-btn,
    body:has(.fi-resource-verifications) .pd-verification-column-panel button[type="submit"] {
        min-height: 2.25rem !important;
        border-radius: 0.65rem !important;
        border: 1px solid rgba(15, 118, 110, 0.22) !important;
        background: #0f766e !important;
        color: #ffffff !important;
        box-shadow: none !important;
        font-size: 0.78rem !important;
        font-weight: 850 !important;
    }

    body:has(.fi-resource-verifications) .pd-verification-column-panel .fi-btn:hover,
    body:has(.fi-resource-verifications) .pd-verification-column-panel button[type="submit"]:hover {
        background: #0d9488 !important;
        color: #ffffff !important;
    }

    body:has(.fi-resource-verifications) .pd-column-menu-actions {
        flex: 0 0 auto !important;
        position: sticky !important;
        bottom: 0 !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 0.45rem !important;
        margin: 0 !important;
        padding: 0.62rem 0.25rem 0.1rem !important;
        border-top: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
    }

    body:has(.fi-resource-verifications) .pd-column-menu-extra-done {
        display: none !important;
    }
    /* Verification Requests Option A final alignment for current Filament markup. */
    .fi-resource-verifications .fi-header-actions a[href*="/import"],
    .fi-resource-verifications .fi-ac a[href*="/import"] {
        border-color: #d0d5dd !important;
        background: #ffffff !important;
        color: #344054 !important;
    }

    .fi-resource-verifications .fi-header-actions a[href*="/import"]:hover,
    .fi-resource-verifications .fi-ac a[href*="/import"]:hover {
        border-color: #98a2b3 !important;
        background: #f9fafb !important;
        color: #101828 !important;
    }

    .fi-resource-verifications .fi-header-actions a[href$="/create"],
    .fi-resource-verifications .fi-ac a[href$="/create"] {
        border-color: #0f766e !important;
        background: #0f766e !important;
        color: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta-ctn,
    .fi-resource-verifications .fi-ta {
        overflow: visible !important;
        border: 1px solid #dce3e8 !important;
        border-radius: 0.5rem !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-ta-header-ctn {
        margin-top: 0 !important;
    }

    .fi-resource-verifications .fi-ta-header-ctn > .verification-queue-kpis {
        display: flex !important;
        min-height: 3.1rem !important;
        align-items: stretch !important;
        flex-wrap: nowrap !important;
        gap: 1.35rem !important;
        overflow-x: auto !important;
        margin: 0 !important;
        padding: 0 1rem !important;
        border: 0 !important;
        border-bottom: 1px solid #e4e9ed !important;
        background: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta-header-ctn > .verification-queue-kpis .verification-queue-kpi {
        display: inline-flex !important;
        min-height: 3.1rem !important;
        align-items: center !important;
        flex: 0 0 auto !important;
        border: 0 !important;
        border-bottom: 2px solid transparent !important;
        border-radius: 0 !important;
        background: transparent !important;
        color: #667085 !important;
        padding: 0 !important;
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        text-decoration: none !important;
    }

    .fi-resource-verifications .fi-ta-header-ctn > .verification-queue-kpis .verification-queue-kpi:hover {
        color: #0f766e !important;
    }

    .fi-resource-verifications .fi-ta-header-ctn > .verification-queue-kpis .verification-queue-kpi.is-active {
        border-bottom-color: #0f8a82 !important;
        color: #0f766e !important;
    }

    .fi-resource-verifications .fi-ta-header-toolbar {
        display: flex !important;
        min-height: 3.65rem !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
        gap: 0.75rem !important;
        margin: 0 !important;
        padding: 0.7rem 1rem !important;
        border-bottom: 1px solid #e4e9ed !important;
        background: #ffffff !important;
    }

    .fi-resource-verifications .fi-ta-header-toolbar > :first-child:empty {
        display: none !important;
    }

    .fi-resource-verifications .fi-ta-header-toolbar > :last-child {
        display: flex !important;
        width: 100% !important;
        align-items: center !important;
        gap: 0.55rem !important;
        margin-left: 0 !important;
    }

    .fi-resource-verifications .fi-ta-header-toolbar .fi-ta-search-field {
        order: -1 !important;
        width: min(21rem, 100%) !important;
        margin-right: auto !important;
    }

    .fi-resource-verifications .fi-ta-header-toolbar .fi-dropdown,
    .fi-resource-verifications .fi-ta-header-toolbar .fi-modal-trigger {
        flex: 0 0 auto !important;
    }

    .fi-resource-verifications .fi-ta-header-toolbar .fi-icon-btn {
        display: inline-flex !important;
        width: 2.3rem !important;
        height: 2.3rem !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 0.4rem !important;
        background: #ffffff !important;
        color: #475467 !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-ta-cell:has(> .fi-ta-actions) {
        min-width: 7.75rem !important;
    }

    .fi-resource-verifications .fi-ta-cell > .fi-ta-actions {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 0.5rem !important;
        white-space: nowrap !important;
    }

    .fi-resource-verifications .fi-ta-cell > .fi-ta-actions .fi-icon-btn {
        flex: 0 0 2rem !important;
    }

    @media (max-width: 900px) {
        .fi-resource-verifications .fi-ta-header-toolbar {
            align-items: stretch !important;
            flex-wrap: wrap !important;
        }

        .fi-resource-verifications .fi-ta-header-toolbar > :last-child {
            flex-wrap: wrap !important;
        }

        .fi-resource-verifications .fi-ta-header-toolbar .fi-ta-search-field {
            width: 100% !important;
        }
    }
    /* Verification Requests secondary page header. */
    .fi-resource-verifications .fi-main {
        padding-top: 0 !important;
    }

    .fi-resource-verifications .fi-header {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        grid-template-areas: "copy actions" !important;
        align-items: center !important;
        gap: 1rem !important;
        margin: 0 calc(-1 * clamp(1.25rem, 2vw, 2rem)) !important;
        padding: 0.9rem clamp(1.25rem, 2vw, 2rem) 1rem !important;
        border: 0 !important;
        border-bottom: 1px solid #e4e7ec !important;
        border-radius: 0 !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .fi-resource-verifications .fi-header > div:first-child {
        display: flex !important;
        min-width: 0 !important;
        flex-direction: column !important;
        grid-area: copy !important;
    }

    .fi-resource-verifications .fi-header-actions-ctn {
        grid-area: actions !important;
        align-self: center !important;
        justify-self: end !important;
        margin: 0 !important;
    }

    .fi-resource-verifications .fi-breadcrumbs,
    .fi-resource-verifications .fi-header .fi-breadcrumbs {
        order: 0 !important;
        display: flex !important;
        align-items: center !important;
        margin: 0 0 0.42rem !important;
    }

    .fi-resource-verifications .fi-breadcrumbs::before {
        display: none !important;
        content: none !important;
    }

    .fi-resource-verifications .fi-breadcrumbs ol,
    .fi-resource-verifications .fi-breadcrumbs-list {
        min-height: 0 !important;
        gap: 0.4rem !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
    }

    .fi-resource-verifications .fi-breadcrumbs li,
    .fi-resource-verifications .fi-breadcrumbs a,
    .fi-resource-verifications .fi-breadcrumbs span {
        color: #667085 !important;
        font-size: 0.74rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
    }

    .fi-resource-verifications .fi-breadcrumbs li:last-child,
    .fi-resource-verifications .fi-breadcrumbs li:last-child span {
        color: #344054 !important;
        font-weight: 700 !important;
    }

    .fi-resource-verifications .fi-breadcrumbs svg {
        width: 0.8rem !important;
        height: 0.8rem !important;
        color: #98a2b3 !important;
    }

    .fi-resource-verifications .fi-header-heading {
        order: 1 !important;
        font-size: 1.65rem !important;
        font-weight: 800 !important;
        line-height: 1.2 !important;
    }

    .fi-resource-verifications .fi-header-subheading {
        order: 2 !important;
        margin-top: 0.22rem !important;
        color: #667085 !important;
        font-size: 0.82rem !important;
        line-height: 1.4 !important;
    }

    .fi-resource-verifications .fi-header-actions .fi-btn,
    .fi-resource-verifications .fi-header-actions-ctn .fi-btn {
        min-height: 2.35rem !important;
        border-width: 1px !important;
        border-style: solid !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04) !important;
    }

    .fi-resource-verifications .fi-header-actions a[href*="/import"],
    .fi-resource-verifications .fi-header-actions-ctn a[href*="/import"] {
        border-color: #d0d5dd !important;
        background: #ffffff !important;
        color: #344054 !important;
    }

    .fi-resource-verifications .fi-header-actions a[href$="/create"],
    .fi-resource-verifications .fi-header-actions-ctn a[href$="/create"] {
        border-color: rgba(11, 107, 100, 0.72) !important;
        background: #0f766e !important;
        color: #ffffff !important;
    }

    @media (max-width: 760px) {
        .fi-resource-verifications .fi-header {
            display: flex !important;
            align-items: stretch !important;
            flex-direction: column !important;
        }

        .fi-resource-verifications .fi-header-actions-ctn,
        .fi-resource-verifications .fi-header-actions {
            width: 100% !important;
            justify-self: stretch !important;
        }
    }
    /* Remove Filament's reserved gap between the global and secondary headers. */
    body .fi-resource-verifications .fi-page-header-main-ctn {
        gap: 1.25rem !important;
        padding-top: 0 !important;
        padding-bottom: 1.25rem !important;
    }

    body .fi-resource-verifications .fi-header {
        margin-bottom: 0 !important;
    }

    body .fi-resource-verifications .fi-header > div:first-child > .fi-breadcrumbs {
        order: -10 !important;
        display: flex !important;
        margin: 0 0 0.42rem !important;
    }

    body .fi-resource-verifications .fi-header > div:first-child > .fi-header-heading {
        order: 0 !important;
    }

    body .fi-resource-verifications .fi-header > div:first-child > .fi-header-subheading {
        order: 1 !important;
    }

    body .fi-resource-verifications .fi-header-actions-ctn .fi-ac a.fi-btn[href*="/import"] {
        border: 1px solid #d0d5dd !important;
        background: #ffffff !important;
        color: #344054 !important;
    }

    body .fi-resource-verifications .fi-header-actions-ctn .fi-ac a.fi-btn[href*="/import"]:hover {
        border-color: #98a2b3 !important;
        background: #f9fafb !important;
        color: #101828 !important;
    }

    body .fi-resource-verifications .fi-header-actions-ctn .fi-ac a.fi-btn[href$="/create"] {
        border: 1px solid rgba(11, 107, 100, 0.72) !important;
        background: #0f766e !important;
        color: #ffffff !important;
    }
    /* Make the global and secondary headers one continuous shell. */
    body:has(.fi-resource-verifications) .fi-main {
        padding-top: 0 !important;
    }

    body .fi-resource-verifications .pd-hero-header__content {
        gap: 0 !important;
    }

    body .fi-resource-verifications .pd-hero-header__content > div:has(> .fi-breadcrumbs) {
        order: 1 !important;
        display: flex !important;
        width: auto !important;
        align-items: center !important;
        margin: 0.42rem 0 0 !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
    }

    body .fi-resource-verifications .pd-hero-header__content > div:has(> .fi-header-heading) {
        order: 0 !important;
        gap: 0.22rem !important;
    }

    /* Clinic Verification Requests uses the same operational table language. */
    .fi-resource-verification-requests .fi-header-actions-ctn a[href*="/import"],
    .fi-resource-verification-requests .fi-header-actions a[href*="/import"] {
        border: 1px solid #d0d5dd !important;
        background: #ffffff !important;
        color: #344054 !important;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04) !important;
    }

    .fi-resource-verification-requests .fi-header-actions-ctn a[href*="/import"]:hover,
    .fi-resource-verification-requests .fi-header-actions a[href*="/import"]:hover {
        border-color: #98a2b3 !important;
        background: #f9fafb !important;
        color: #101828 !important;
    }

    .fi-resource-verification-requests .fi-header-actions-ctn a[href$="/create"],
    .fi-resource-verification-requests .fi-header-actions a[href$="/create"] {
        border: 1px solid rgba(11, 107, 100, 0.72) !important;
        background: #0f766e !important;
        color: #ffffff !important;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08) !important;
    }

    .fi-resource-verification-requests .fi-ta-header-cell,
    .fi-resource-verification-requests .fi-ta-header-cell button,
    .fi-resource-verification-requests .fi-ta-header-cell span {
        color: #475467 !important;
        font-size: 0.76rem !important;
        font-weight: 750 !important;
    }

    .fi-resource-verification-requests .pd-verification-primary-cell,
    .fi-resource-verification-requests .pd-verification-sla {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.2rem !important;
    }

    .fi-resource-verification-requests .pd-verification-primary-cell {
        min-width: 8.75rem !important;
    }

    .fi-resource-verification-requests .pd-verification-sla {
        min-width: 9rem !important;
    }

    .fi-resource-verification-requests .pd-verification-primary-cell strong,
    .fi-resource-verification-requests .pd-verification-sla strong {
        color: #101828 !important;
        font-size: 0.8rem !important;
        font-weight: 750 !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }

    .fi-resource-verification-requests .pd-verification-primary-cell > span,
    .fi-resource-verification-requests .pd-verification-sla > span {
        color: #667085 !important;
        font-size: 0.72rem !important;
        font-weight: 550 !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }

    .fi-resource-verification-requests .pd-verification-sla--overdue > span {
        color: #b42318 !important;
    }

    .fi-resource-verification-requests .pd-verification-sla--due_today > span,
    .fi-resource-verification-requests .pd-verification-sla--paused_waiting_clinic > span {
        color: #b54708 !important;
    }

</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const enhanceVerificationColumnMenus = () => {
            if (! document.querySelector('.fi-resource-verifications')) {
                return;
            }

            document.querySelectorAll('.fi-dropdown-panel').forEach((panel) => {
                const panelText = panel.textContent || '';
                const looksLikeColumnPanel = panelText.includes('Columns')
                    || (
                        panelText.includes('Reference')
                        && panelText.includes('Patient')
                        && panelText.includes('Insurance provider')
                    );

                if (! looksLikeColumnPanel) {
                    return;
                }

                panel.classList.add('pd-verification-column-panel');

                const applyButton = Array.from(panel.querySelectorAll('button'))
                    .find((button) => (button.textContent || '').trim().includes('Apply columns'));

                if (! applyButton) {
                    return;
                }

                Array.from(panel.querySelectorAll('button')).forEach((button) => {
                    if ((button.textContent || '').trim() === 'Done') {
                        button.classList.add('pd-column-menu-extra-done');
                    }
                });

                const footer = applyButton.closest('div') || applyButton.parentElement;
                const contentRoot = footer?.parentElement;

                if (! contentRoot) {
                    return;
                }

                contentRoot.classList.add('pd-column-menu-content');
                footer.classList.add('pd-column-menu-actions');

                let list = contentRoot.querySelector(':scope > .pd-column-menu-list');

                if (! list) {
                    list = document.createElement('div');
                    list.className = 'pd-column-menu-list';
                    contentRoot.insertBefore(list, footer);
                }

                Array.from(contentRoot.children).forEach((child) => {
                    if (child !== footer && child !== list) {
                        list.appendChild(child);
                    }
                });
            });
        };

        enhanceVerificationColumnMenus();

        new MutationObserver(enhanceVerificationColumnMenus).observe(document.body, {
            childList: true,
            subtree: true,
        });
    });
</script>
