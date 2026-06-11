<div class="admin-sidebar-header-toggle">
    <button
        type="button"
        x-data
        x-on:click="
            document.documentElement.classList.toggle('verification-sidebar-collapsed');
            localStorage.setItem(
                'verification-sidebar-collapsed',
                document.documentElement.classList.contains('verification-sidebar-collapsed') ? '1' : '0'
            );
            window.dispatchEvent(new Event('resize'));
        "
        class="admin-sidebar-toggle-btn"
        title="Toggle navigation"
        aria-label="Toggle navigation"
    >
        <span class="admin-sidebar-toggle-icon admin-sidebar-toggle-icon--collapse">
            <x-heroicon-o-chevron-double-left style="width: 16px; height: 16px;" />
        </span>
        <span class="admin-sidebar-toggle-icon admin-sidebar-toggle-icon--expand">
            <x-heroicon-o-chevron-double-right style="width: 16px; height: 16px;" />
        </span>
    </button>
</div>

<script>
    (() => {
        const key = 'verification-sidebar-collapsed';
        const root = document.documentElement;

        const applyState = () => {
            if (localStorage.getItem(key) === '1') {
                root.classList.add('verification-sidebar-collapsed');
            } else {
                root.classList.remove('verification-sidebar-collapsed');
            }
        };

        applyState();
        document.addEventListener('livewire:navigated', applyState);
    })();
</script>
