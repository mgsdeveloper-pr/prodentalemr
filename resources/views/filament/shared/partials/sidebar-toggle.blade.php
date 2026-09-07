<div class="app-sidebar-header-toggle">
    <button
        type="button"
        x-data
        x-on:click="
            $store.sidebar.open();
            document.documentElement.classList.toggle('app-sidebar-collapsed');
            localStorage.setItem(
                'app-sidebar-collapsed',
                document.documentElement.classList.contains('app-sidebar-collapsed') ? '1' : '0'
            );
            window.dispatchEvent(new CustomEvent('app-sidebar-collapse-changed', {
                detail: { collapsed: document.documentElement.classList.contains('app-sidebar-collapsed') }
            }));
            window.dispatchEvent(new Event('resize'));
        "
        class="app-sidebar-toggle-btn"
        title="Toggle navigation"
        aria-label="Toggle navigation"
    >
        <span class="app-sidebar-toggle-icon app-sidebar-toggle-icon--collapse">
            @svg('heroicon-o-chevron-double-left', '', ['style' => 'width: 16px; height: 16px;', 'aria-hidden' => 'true'])
        </span>
        <span class="app-sidebar-toggle-icon app-sidebar-toggle-icon--expand">
            @svg('heroicon-o-chevron-double-right', '', ['style' => 'width: 16px; height: 16px;', 'aria-hidden' => 'true'])
        </span>
    </button>
</div>

<script>
    (() => {
        const key = 'app-sidebar-collapsed';
        const legacyKey = 'verification-sidebar-collapsed';
        const root = document.documentElement;

        const syncNavigationLabels = (isCollapsed) => {
            document.querySelectorAll('.fi-sidebar-item-btn').forEach((item) => {
                const label = item.querySelector('.fi-sidebar-item-label')?.textContent?.trim();

                if (!label) return;

                if (isCollapsed) {
                    item.setAttribute('title', label);
                    item.setAttribute('aria-label', label);
                } else {
                    item.removeAttribute('title');
                    item.removeAttribute('aria-label');
                }
            });
        };

        const applyState = () => {
            const isCollapsed = localStorage.getItem(key) === '1' || localStorage.getItem(legacyKey) === '1';

            // Livewire navigation can retain the offset from a horizontally
            // scrolled data region. Keep the application rail viewport-aligned.
            if (document.scrollingElement?.scrollLeft) {
                document.scrollingElement.scrollLeft = 0;
            }

            root.classList.toggle('app-sidebar-collapsed', isCollapsed);
            localStorage.setItem(key, isCollapsed ? '1' : '0');
            localStorage.removeItem(legacyKey);

            // Filament also persists whether its sidebar is mounted. Keep that
            // state open on desktop; our class controls expanded vs icon rail.
            if (window.innerWidth >= 1024 && window.Alpine?.store('sidebar')) {
                window.Alpine.store('sidebar').open();
            }

            syncNavigationLabels(isCollapsed);
        };

        applyState();
        document.addEventListener('alpine:initialized', applyState, { once: true });
        document.addEventListener('livewire:navigated', applyState);
        window.addEventListener('pageshow', applyState);
        window.addEventListener('app-sidebar-collapse-changed', (event) => {
            syncNavigationLabels(Boolean(event.detail?.collapsed));
        });
    })();
</script>
