@php
    $workspaceLabel = match ($workspace) {
        'platform' => 'Administration',
        'verification' => 'Verification',
        'organization' => 'Organization',
        'dso' => 'Organization',
        default => ucfirst((string) $workspace),
    };

    $user = auth()->user();
    $panelRegistry = app(\Filament\PanelRegistry::class);
    $workspaceLinks = collect([
        'platform' => ['panel' => 'saas', 'label' => 'Administration', 'url' => url('/saas')],
        'verification' => ['panel' => 'admin', 'label' => 'Verification', 'url' => url('/verification')],
        'organization' => ['panel' => 'clinic', 'label' => 'Organization', 'url' => url('/clinic')],
        'dso' => ['panel' => 'dso', 'label' => 'DSO', 'url' => url('/dso')],
    ])
        ->filter(fn (array $item): bool => $user?->canAccessPanel($panelRegistry->get($item['panel'])) ?? false)
        ->all();
    $visibleWorkspaceLabels = collect($workspaceLinks)->pluck('label')->all();
    $userName = $user?->name ?: 'Profile';
    $userEmail = $user?->email;
    $userRole = trim((string) ($user?->getPrimaryRoleLabel() ?? 'User'));
    $userInitials = collect(explode(' ', trim($userName)))
        ->filter()
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('') ?: 'PD';
    $searchWorkspace = $workspace === 'organization' ? 'clinic' : $workspace;
    $searchPlaceholder = match ($searchWorkspace) {
        'platform' => 'Search clients, clinics, invoices...',
        'verification' => 'Search requests, patients, clinics...',
        'clinic' => 'Search requests, patients, appointments...',
        'dso' => 'Search organizations and clinics...',
        default => 'Search records...',
    };
    $searchScopeLabel = match ($searchWorkspace) {
        'platform' => 'Platform records',
        'verification' => 'Assigned clinic records',
        'clinic' => 'Selected clinic records',
        'dso' => 'DSO records',
        default => 'Permitted records',
    };
@endphp

<section
    class="pd-appshell-global-header"
    aria-label="Global application header"
    x-data="pdGlobalSearch({ workspace: @js($searchWorkspace), endpoint: @js(route('app.global-search', [], false)) })"
    @keydown.window="handleGlobalKeydown($event)"
    x-on:livewire:navigating.window="forceClose()"
>
    <div class="pd-appshell-global-header__left">
        <a class="pd-appshell-global-header__brand" href="{{ url('/') }}" aria-label="ProDental home">
            <span class="pd-appshell-global-header__logo" aria-hidden="true">PD</span>
            <span class="pd-appshell-global-header__brand-name">ProDental</span>
        </a>

        <details class="pd-appshell-workspace-switcher">
            <summary class="pd-appshell-workspace-switcher__summary" aria-label="Workspace switcher">
                <span>{{ $workspaceLabel }}</span>
                @svg('heroicon-o-chevron-down', 'pd-appshell-workspace-switcher__icon', ['aria-hidden' => 'true'])
            </summary>
            <nav class="pd-appshell-workspace-switcher__menu" aria-label="Workspace switcher">
                @foreach ($workspaceLinks as $key => $item)
                    <a
                        class="pd-appshell-workspace-switcher__link {{ $key === $workspace ? 'pd-appshell-workspace-switcher__link--active' : '' }}"
                        href="{{ $item['url'] }}"
                        @if ($key === $workspace) aria-current="page" @endif
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
                @foreach (['Reports', 'Revenue', 'Administration', 'Future AI'] as $futureWorkspace)
                    @unless (in_array($futureWorkspace, $visibleWorkspaceLabels, true))
                        <span class="pd-appshell-workspace-switcher__future">{{ $futureWorkspace }}</span>
                    @endunless
                @endforeach
            </nav>
        </details>
    </div>

    <button
        type="button"
        class="pd-appshell-global-header__search"
        aria-label="Open global search"
        :aria-expanded="open.toString()"
        @click="openSearch()"
    >
        @svg('heroicon-o-magnifying-glass', 'pd-appshell-global-header__search-icon', ['aria-hidden' => 'true'])
        <span class="pd-appshell-global-header__search-text" data-appshell-slot="global-search">{{ $searchPlaceholder }}</span>
        <span class="pd-appshell-global-header__kbd" aria-hidden="true">Ctrl + K</span>
    </button>

    <div class="pd-appshell-global-header__utilities" aria-label="User utilities">
        <button type="button" class="pd-appshell-icon-button pd-appshell-global-header__mobile-search" aria-label="Open global search" @click="openSearch()">
            @svg('heroicon-o-magnifying-glass', 'pd-appshell-icon-button__icon', ['aria-hidden' => 'true'])
        </button>
        <button type="button" class="pd-appshell-icon-button" data-appshell-slot="notifications" aria-label="Alerts">
            @svg('heroicon-o-bell', 'pd-appshell-icon-button__icon', ['aria-hidden' => 'true'])
        </button>
        <button type="button" class="pd-appshell-icon-button" data-appshell-slot="help" aria-label="Help">
            @svg('heroicon-o-question-mark-circle', 'pd-appshell-icon-button__icon', ['aria-hidden' => 'true'])
        </button>
        <details class="pd-appshell-user-menu" data-appshell-slot="user-menu">
            <summary class="pd-appshell-user-menu__summary" aria-label="User menu">
                <span class="pd-appshell-user-menu__avatar" aria-hidden="true">{{ $userInitials }}</span>
                <span class="pd-appshell-user-menu__summary-text">
                    <span class="pd-appshell-user-menu__summary-name">{{ $userName }}</span>
                    <span class="pd-appshell-user-menu__summary-role">{{ $userRole }}</span>
                </span>
                @svg('heroicon-o-chevron-down', 'pd-appshell-user-menu__chevron', ['aria-hidden' => 'true'])
            </summary>
            <div class="pd-appshell-user-menu__panel">
                <div class="pd-appshell-user-menu__identity">
                    <span class="pd-appshell-user-menu__name">{{ $userName }}</span>
                    @if ($userEmail)
                        <span class="pd-appshell-user-menu__email">{{ $userEmail }}</span>
                    @endif
                </div>
                <a class="pd-appshell-user-menu__link" href="{{ route('profile.edit') }}">Profile settings</a>
                <a class="pd-appshell-user-menu__link" href="{{ $signOutUrl ?? url('/logout') }}">Sign out</a>
            </div>
        </details>
    </div>

    <div
        class="pd-global-search-backdrop"
        x-cloak
        x-show="open"
        x-transition.opacity.duration.150ms
        @click.self="closeSearch()"
        role="presentation"
    >
        <section
            class="pd-global-search-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="pd-global-search-title"
            @click.stop
        >
            <header class="pd-global-search-dialog__header">
                <div>
                    <span class="pd-global-search-dialog__eyebrow">Global search</span>
                    <h2 id="pd-global-search-title">Find anything you can access</h2>
                </div>
                <button type="button" class="pd-global-search-dialog__close" aria-label="Close global search" @click="closeSearch()">
                    @svg('heroicon-o-x-mark', 'pd-global-search-dialog__close-icon', ['aria-hidden' => 'true'])
                </button>
            </header>

            <div class="pd-global-search-dialog__scope">
                @svg('heroicon-o-lock-closed', 'pd-global-search-dialog__scope-icon', ['aria-hidden' => 'true'])
                <span>Searching in <strong>{{ $searchScopeLabel }}</strong></span>
            </div>

            <label class="pd-global-search-dialog__input-wrap">
                <span class="sr-only">Search permitted records</span>
                @svg('heroicon-o-magnifying-glass', 'pd-global-search-dialog__input-icon', ['aria-hidden' => 'true'])
                <input
                    x-ref="searchInput"
                    x-model="query"
                    @input.debounce.250ms="fetchResults()"
                    @keydown.down.prevent="moveSelection(1)"
                    @keydown.up.prevent="moveSelection(-1)"
                    @keydown.enter.prevent="openSelected()"
                    type="search"
                    role="combobox"
                    aria-autocomplete="list"
                    :aria-expanded="open.toString()"
                    class="pd-global-search-dialog__input"
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                >
                <span class="pd-global-search-dialog__esc" aria-hidden="true">Esc</span>
            </label>

            <div class="pd-global-search-dialog__results" role="listbox" aria-label="Search results">
                <div class="pd-global-search-dialog__state" x-show="loading">
                    <span class="pd-global-search-dialog__spinner" aria-hidden="true"></span>
                    <span>Searching permitted records...</span>
                </div>

                <template x-if="! loading && query.length === 1">
                    <div class="pd-global-search-dialog__state">
                        @svg('heroicon-o-magnifying-glass', 'pd-global-search-dialog__state-icon', ['aria-hidden' => 'true'])
                        <strong>Type one more character</strong>
                        <span>Search begins after two characters.</span>
                    </div>
                </template>

                <template x-if="! loading && query.length >= 2 && resultCount === 0">
                    <div class="pd-global-search-dialog__state">
                        @svg('heroicon-o-document-magnifying-glass', 'pd-global-search-dialog__state-icon', ['aria-hidden' => 'true'])
                        <strong>No matching records</strong>
                        <span>Try a patient, clinic, reference number, or insurance name.</span>
                    </div>
                </template>

                <template x-for="group in groups" :key="group.key">
                    <div class="pd-global-search-group">
                        <h3 x-text="group.label"></h3>
                        <template x-for="item in group.items" :key="item.url + item.title">
                            <a
                                class="pd-global-search-result"
                                :class="{ 'pd-global-search-result--selected': item._index === selectedIndex }"
                                :href="item.url"
                                role="option"
                                :aria-selected="(item._index === selectedIndex).toString()"
                                @mouseenter="selectedIndex = item._index"
                                @click="closeSearch()"
                            >
                                <span class="pd-global-search-result__icon" aria-hidden="true">
                                    @svg('heroicon-o-document-text')
                                </span>
                                <span class="pd-global-search-result__copy">
                                    <strong x-text="item.title"></strong>
                                    <small x-text="item.subtitle"></small>
                                </span>
                                <span class="pd-global-search-result__meta" x-text="item.meta"></span>
                                @svg('heroicon-o-arrow-up-right', 'pd-global-search-result__arrow', ['aria-hidden' => 'true'])
                            </a>
                        </template>
                    </div>
                </template>
            </div>

            <footer class="pd-global-search-dialog__footer">
                <span><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
                <span><kbd>Enter</kbd> Open</span>
                <span><kbd>Esc</kbd> Close</span>
                <strong>Results respect your workspace and clinic access.</strong>
            </footer>
        </section>
    </div>
</section>

@once
    <style>
        [x-cloak] { display: none !important; }
        .pd-global-search-backdrop { position: fixed; inset: 0; z-index: 10000; display: flex; align-items: flex-start; justify-content: center; padding: min(14vh, 120px) 24px 24px; background: rgba(15, 23, 42, .48); backdrop-filter: blur(2px); }
        .pd-global-search-dialog { display: flex; width: min(720px, 100%); max-height: min(720px, 78vh); flex-direction: column; overflow: hidden; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .24); color: #0f172a; }
        .pd-global-search-dialog__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 20px 20px 10px; }
        .pd-global-search-dialog__eyebrow { display: block; margin-bottom: 3px; color: #087f73; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .pd-global-search-dialog__header h2 { margin: 0; font-size: 20px; line-height: 1.3; }
        .pd-global-search-dialog__close { display: inline-flex; width: 36px; height: 36px; flex: 0 0 36px; align-items: center; justify-content: center; border: 1px solid #dbe4ee; border-radius: 6px; background: #fff; color: #475569; cursor: pointer; }
        .pd-global-search-dialog__close:hover { background: #f8fafc; color: #0f766e; }
        .pd-global-search-dialog__close-icon { width: 18px; height: 18px; }
        .pd-global-search-dialog__scope { display: flex; align-items: center; gap: 7px; margin: 0 20px 10px; padding: 8px 11px; border: 1px solid #bae6df; border-radius: 6px; background: #f0fdfa; color: #475569; font-size: 12px; }
        .pd-global-search-dialog__scope strong { color: #0f766e; }
        .pd-global-search-dialog__scope-icon { width: 14px; height: 14px; }
        .pd-global-search-dialog__input-wrap { display: flex; min-height: 50px; align-items: center; gap: 10px; margin: 0 20px 12px; padding: 0 12px; border: 1px solid #99d5cf; border-radius: 7px; box-shadow: 0 0 0 3px rgba(13, 148, 136, .08); }
        .pd-global-search-dialog__input-icon { width: 19px; height: 19px; flex: 0 0 auto; color: #0f766e; }
        .pd-global-search-dialog__input { min-width: 0; flex: 1; border: 0; outline: 0; background: transparent; color: #0f172a; font-size: 14px; }
        .pd-global-search-dialog__input::-webkit-search-cancel-button { display: none; }
        .pd-global-search-dialog__esc, .pd-global-search-dialog__footer kbd { border: 1px solid #dbe4ee; border-radius: 4px; background: #f8fafc; color: #475569; font-size: 10px; font-weight: 700; line-height: 1; padding: 5px 6px; }
        .pd-global-search-dialog__results { min-height: 220px; flex: 1; overflow-y: auto; border-top: 1px solid #e2e8f0; }
        .pd-global-search-dialog__state { display: flex; min-height: 220px; flex-direction: column; align-items: center; justify-content: center; gap: 7px; padding: 32px; color: #64748b; text-align: center; }
        .pd-global-search-dialog__state strong { color: #1e293b; }
        .pd-global-search-dialog__state-icon { width: 26px; height: 26px; color: #94a3b8; }
        .pd-global-search-dialog__spinner { width: 24px; height: 24px; border: 3px solid #ccfbf1; border-top-color: #0d9488; border-radius: 50%; animation: pd-global-search-spin .7s linear infinite; }
        @keyframes pd-global-search-spin { to { transform: rotate(360deg); } }
        .pd-global-search-group { padding: 12px 12px 4px; }
        .pd-global-search-group h3 { margin: 0 8px 7px; color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .pd-global-search-result { display: flex; min-height: 56px; align-items: center; gap: 11px; padding: 8px; border-radius: 6px; color: inherit; text-decoration: none; }
        .pd-global-search-result:hover, .pd-global-search-result--selected { background: #f0fdfa; }
        .pd-global-search-result__icon { display: inline-flex; width: 34px; height: 34px; flex: 0 0 34px; align-items: center; justify-content: center; border: 1px solid #ccfbf1; border-radius: 6px; background: #f0fdfa; color: #0f766e; }
        .pd-global-search-result__icon svg { width: 17px; height: 17px; }
        .pd-global-search-result__copy { min-width: 0; flex: 1; }
        .pd-global-search-result__copy strong, .pd-global-search-result__copy small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pd-global-search-result__copy strong { font-size: 13px; }
        .pd-global-search-result__copy small { margin-top: 2px; color: #64748b; font-size: 11px; }
        .pd-global-search-result__meta { color: #64748b; font-size: 11px; }
        .pd-global-search-result__arrow { width: 16px; height: 16px; color: #94a3b8; }
        .pd-global-search-dialog__footer { display: flex; align-items: center; gap: 14px; padding: 10px 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 10px; }
        .pd-global-search-dialog__footer span { display: inline-flex; align-items: center; gap: 4px; }
        .pd-global-search-dialog__footer strong { margin-left: auto; color: #0f766e; font-size: 10px; }
        .pd-appshell-global-header__mobile-search { display: none !important; }
        body.pd-global-search-open { overflow: hidden; }
        @media (max-width: 860px) {
            .pd-appshell-global-header__mobile-search { display: inline-flex !important; }
            .pd-global-search-backdrop { align-items: stretch; padding: 0; }
            .pd-global-search-dialog { width: 100%; max-height: 100%; border: 0; border-radius: 0; }
            .pd-global-search-dialog__footer strong { display: none; }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pdGlobalSearch', (config) => ({
                open: false,
                query: '',
                groups: [],
                resultCount: 0,
                loading: false,
                selectedIndex: -1,
                controller: null,

                openSearch() {
                    this.open = true;
                    document.body.classList.add('pd-global-search-open');
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                    if (this.groups.length === 0 && this.query.length === 0) this.fetchResults();
                },

                closeSearch() {
                    this.open = false;
                    this.loading = false;
                    this.controller?.abort();
                    this.controller = null;
                    document.body.classList.remove('pd-global-search-open');
                },

                forceClose() {
                    this.closeSearch();
                    this.query = '';
                    this.groups = [];
                    this.resultCount = 0;
                    this.selectedIndex = -1;
                },

                handleGlobalKeydown(event) {
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                        event.preventDefault();
                        this.open ? this.closeSearch() : this.openSearch();
                        return;
                    }
                    if (event.key === 'Escape' && this.open) {
                        event.preventDefault();
                        this.closeSearch();
                    }
                },

                async fetchResults() {
                    if (this.query.length === 1) {
                        this.controller?.abort();
                        this.groups = [];
                        this.resultCount = 0;
                        this.selectedIndex = -1;
                        this.loading = false;
                        return;
                    }

                    this.controller?.abort();
                    const controller = new AbortController();
                    this.controller = controller;
                    this.loading = true;

                    try {
                        const url = new URL(config.endpoint, window.location.origin);
                        url.searchParams.set('workspace', config.workspace);
                        if (this.query) url.searchParams.set('q', this.query);
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: controller.signal,
                        });
                        if (! response.ok) throw new Error('Search request failed');
                        const payload = await response.json();
                        let index = 0;
                        this.groups = (payload.groups || []).map(group => ({
                            ...group,
                            items: (group.items || []).map(item => ({ ...item, _index: index++ })),
                        }));
                        this.resultCount = payload.result_count || 0;
                        this.selectedIndex = this.resultCount ? 0 : -1;
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            this.groups = [];
                            this.resultCount = 0;
                            this.selectedIndex = -1;
                        }
                    } finally {
                        if (this.controller === controller) this.loading = false;
                    }
                },

                moveSelection(direction) {
                    if (! this.resultCount) return;
                    this.selectedIndex = (this.selectedIndex + direction + this.resultCount) % this.resultCount;
                    this.$nextTick(() => document.querySelector('.pd-global-search-result--selected')?.scrollIntoView({ block: 'nearest' }));
                },

                openSelected() {
                    const item = this.groups.flatMap(group => group.items).find(result => result._index === this.selectedIndex);
                    if (item?.url) {
                        this.closeSearch();
                        window.location.assign(item.url);
                    }
                },
            }));
        });
    </script>
@endonce
