<div class="app-sidebar-header-toggle">
    <button
        type="button"
        x-data
        x-on:click="
            document.documentElement.classList.toggle('app-sidebar-collapsed');
            localStorage.setItem(
                'app-sidebar-collapsed',
                document.documentElement.classList.contains('app-sidebar-collapsed') ? '1' : '0'
            );
            window.dispatchEvent(new Event('resize'));
        "
        class="app-sidebar-toggle-btn"
        title="Toggle navigation"
        aria-label="Toggle navigation"
    >
        <span class="app-sidebar-toggle-icon app-sidebar-toggle-icon--collapse">
            <x-heroicon-o-chevron-double-left style="width: 16px; height: 16px;" />
        </span>
        <span class="app-sidebar-toggle-icon app-sidebar-toggle-icon--expand">
            <x-heroicon-o-chevron-double-right style="width: 16px; height: 16px;" />
        </span>
    </button>
</div>

<script>
    (() => {
        const key = 'app-sidebar-collapsed';
        const legacyKey = 'verification-sidebar-collapsed';
        const root = document.documentElement;

        const applyState = () => {
            const isCollapsed = localStorage.getItem(key) === '1' || localStorage.getItem(legacyKey) === '1';

            root.classList.toggle('app-sidebar-collapsed', isCollapsed);
            localStorage.setItem(key, isCollapsed ? '1' : '0');
            localStorage.removeItem(legacyKey);
        };

        applyState();
        document.addEventListener('livewire:navigated', applyState);
    })();
</script>
