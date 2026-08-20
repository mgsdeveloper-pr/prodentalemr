<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $clients = $this->clients();
        $onboardingDrafts = $this->onboardingDrafts();
        $filtersActive = filled($this->search)
            || $this->typeFilter !== 'all'
            || $this->serviceFilter !== 'all'
            || $this->statusFilter !== 'all';
    @endphp

    <style>
        .client-directory { display: grid; gap: 1rem; }
        .client-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .client-summary__item { min-height: 6.25rem; padding: 1rem 1.125rem; border: 1px solid var(--pwdl-border-subtle, #dbe4ee); border-radius: 8px; background: #fff; }
        .client-summary__label { display: block; color: var(--pwdl-text-muted, #64748b); font-size: .73rem; font-weight: 750; }
        .client-summary__value { display: block; margin-top: .45rem; color: var(--pwdl-text-primary, #0f172a); font-size: 1.55rem; font-weight: 800; line-height: 1; }
        .client-summary__note { display: block; margin-top: .55rem; color: var(--pwdl-text-secondary, #475569); font-size: .75rem; }
        .client-table-shell { overflow: hidden; border: 1px solid var(--pwdl-border-subtle, #dbe4ee); border-radius: 8px; background: #fff; }
        .client-toolbar { display: grid; grid-template-columns: minmax(15rem, 1fr) repeat(3, minmax(10rem, .4fr)) auto; gap: .65rem; align-items: center; padding: .85rem 1rem; border-bottom: 1px solid var(--pwdl-border-subtle, #dbe4ee); }
        .client-control { width: 100%; min-height: 2.5rem; border: 1px solid var(--pwdl-border-subtle, #cbd5e1); border-radius: 7px; background: #fff; color: var(--pwdl-text-primary, #0f172a); font-size: .8rem; padding: .55rem .75rem; }
        .client-control:focus { border-color: var(--pwdl-brand-primary, #0f766e); outline: 2px solid color-mix(in srgb, var(--pwdl-brand-primary, #0f766e) 15%, transparent); outline-offset: 0; }
        .client-reset { min-height: 2.5rem; border: 1px solid var(--pwdl-border-subtle, #cbd5e1); border-radius: 7px; background: #fff; color: var(--pwdl-text-secondary, #475569); font-size: .78rem; font-weight: 750; padding: .5rem .8rem; cursor: pointer; }
        .client-reset:hover { border-color: #94a3b8; color: var(--pwdl-text-primary, #0f172a); }
        .client-reset:disabled { cursor: default; opacity: .45; }
        .client-table-scroll { overflow-x: auto; }
        .client-table { width: 100%; min-width: 76rem; border-collapse: collapse; }
        .client-table th { padding: .72rem 1rem; border-bottom: 1px solid var(--pwdl-border-subtle, #dbe4ee); background: #f8fafc; color: #475569; font-size: .7rem; font-weight: 800; text-align: left; }
        .client-table td { padding: .9rem 1rem; border-bottom: 1px solid #e8edf3; color: #334155; font-size: .79rem; vertical-align: middle; }
        .client-table tbody tr:last-child td { border-bottom: 0; }
        .client-table tbody tr:hover { background: #fbfdfd; }
        .client-name { color: #0f172a; font-size: .82rem; font-weight: 800; }
        .client-meta { display: block; margin-top: .2rem; color: #64748b; font-size: .72rem; }
        .client-badge { display: inline-flex; align-items: center; min-height: 1.5rem; border: 1px solid #dbe4ee; border-radius: 999px; background: #f8fafc; color: #475569; font-size: .69rem; font-weight: 750; padding: .2rem .48rem; white-space: nowrap; }
        .client-badge--success { border-color: #bfe8d7; background: #effaf5; color: #047857; }
        .client-badge--warning { border-color: #f2d6a2; background: #fff9ed; color: #9a5b08; }
        .client-badge--danger { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .client-actions { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; white-space: nowrap; }
        .client-action { display: inline-flex; align-items: center; justify-content: center; min-height: 2.1rem; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; color: #334155; font-size: .74rem; font-weight: 800; padding: .42rem .65rem; text-decoration: none; }
        .client-action:hover { border-color: #94a3b8; color: #0f172a; }
        .client-action--primary { border-color: var(--pwdl-brand-primary, #0f766e); background: var(--pwdl-brand-primary, #0f766e); color: #fff; }
        .client-action--primary:hover { color: #fff; filter: brightness(.96); }
        .client-empty { padding: 3.5rem 1.5rem; text-align: center; }
        .client-empty strong { display: block; color: #0f172a; font-size: .95rem; }
        .client-empty span { display: block; margin-top: .35rem; color: #64748b; font-size: .8rem; }
        .client-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .8rem 1rem; border-top: 1px solid var(--pwdl-border-subtle, #dbe4ee); color: #64748b; font-size: .75rem; }
        .client-footer nav { margin-left: auto; }
        @media (max-width: 1100px) { .client-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .client-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); } .client-toolbar .client-search { grid-column: 1 / -1; } }
        @media (max-width: 640px) { .client-summary, .client-toolbar { grid-template-columns: 1fr; } .client-toolbar .client-search { grid-column: auto; } .client-summary__item { min-height: auto; } .client-footer { align-items: flex-start; flex-direction: column; } .client-footer nav { width: 100%; margin-left: 0; } }
    </style>

    <div class="client-directory">
        <section class="client-summary" aria-label="Client summary">
            <div class="client-summary__item"><span class="client-summary__label">Total Clients</span><span class="client-summary__value">{{ number_format($stats['total']) }}</span><span class="client-summary__note">All client organizations</span></div>
            <div class="client-summary__item"><span class="client-summary__label">Active</span><span class="client-summary__value">{{ number_format($stats['active']) }}</span><span class="client-summary__note">Currently available accounts</span></div>
            <div class="client-summary__item"><span class="client-summary__label">Onboarding Incomplete</span><span class="client-summary__value">{{ number_format($stats['onboarding']) }}</span><span class="client-summary__note">Setup still needs completion</span></div>
            <div class="client-summary__item"><span class="client-summary__label">Attention Required</span><span class="client-summary__value">{{ number_format($stats['attention']) }}</span><span class="client-summary__note">Inactive, paused, blocked, or incomplete</span></div>
        </section>

        @if (count($onboardingDrafts))
            <section class="client-table-shell" aria-label="Onboarding in progress">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;border-bottom:1px solid var(--pwdl-border-subtle,#dbe4ee);">
                    <div>
                        <strong class="client-name">Onboarding in progress</strong>
                        <span class="client-meta">Resume an unfinished client setup from its latest saved step.</span>
                    </div>
                </div>
                <div class="client-table-scroll">
                    <table class="client-table" style="min-width:60rem;">
                        <thead><tr><th>Client</th><th>Structure</th><th>Verification Model</th><th>Progress</th><th>Last Activity</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody>
                            @foreach ($onboardingDrafts as $draft)
                                <tr>
                                    <td><span class="client-name">{{ $draft['name'] }}</span><span class="client-meta">{{ $draft['reference'] }}</span></td>
                                    <td>{{ $draft['structure'] }}</td>
                                    <td><span class="client-badge">{{ $draft['verification_model'] }}</span></td>
                                    <td>Step {{ $draft['progress'] }}</td>
                                    <td>{{ $draft['updated'] }}</td>
                                    <td><div class="client-actions"><a class="client-action client-action--primary" href="{{ $draft['url'] }}">Resume Setup</a></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="client-table-shell" aria-label="Client directory">
            <div class="client-toolbar">
                <input class="client-control client-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search client, owner, email, or DSO" aria-label="Search clients">
                <select class="client-control" wire:model.live="typeFilter" aria-label="Filter by client type">
                    <option value="all">All client types</option><option value="solo">Solo Practice</option><option value="multi_location">Multi Location</option><option value="dso">DSO Organization</option>
                </select>
                <select class="client-control" wire:model.live="serviceFilter" aria-label="Filter by verification model">
                    <option value="all">All verification models</option><option value="managed">Managed Service</option><option value="self">Self-Managed</option><option value="hybrid">Hybrid</option>
                </select>
                <select class="client-control" wire:model.live="statusFilter" aria-label="Filter by status">
                    <option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="onboarding">Onboarding Incomplete</option>
                </select>
                <button class="client-reset" type="button" wire:click="resetFilters" @disabled(! $filtersActive)>Clear</button>
            </div>

            @if ($clients->count())
                <div class="client-table-scroll">
                    <table class="client-table">
                        <thead><tr><th>Client</th><th>Type</th><th>Clinics</th><th>Verification Model</th><th>Subscription</th><th>Onboarding</th><th>Status</th><th style="text-align: right;">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($clients as $organization)
                                @php($client = $this->clientRow($organization))
                                <tr wire:key="client-{{ $organization->getKey() }}">
                                    <td><span class="client-name">{{ $organization->name }}</span><span class="client-meta">{{ $organization->dso?->name ?? ($organization->email ?: 'Independent client') }}</span></td>
                                    <td>{{ $client['type'] }}</td>
                                    <td><span class="client-name">{{ number_format($organization->clinics_count) }}</span><span class="client-meta">{{ number_format($organization->locations_count) }} locations · {{ number_format($organization->users_count) }} users</span></td>
                                    <td><span class="client-badge">{{ $client['service_model'] }}</span></td>
                                    <td><span class="client-name">{{ $client['subscription'] }}</span><span class="client-meta">{{ $client['subscription_status'] }}</span></td>
                                    <td><span class="client-badge {{ $client['onboarding'] === 'Complete' ? 'client-badge--success' : 'client-badge--warning' }}">{{ $client['onboarding'] }}</span></td>
                                    <td><span class="client-badge {{ $client['status'] === 'Active' ? 'client-badge--success' : 'client-badge--danger' }}">{{ $client['status'] }}</span></td>
                                    <td><div class="client-actions"><a class="client-action" href="{{ $client['view_url'] }}">View</a><a class="client-action client-action--primary" href="{{ $client['manage_url'] }}">Manage Client</a></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="client-footer"><span>Showing {{ $clients->firstItem() }}–{{ $clients->lastItem() }} of {{ number_format($clients->total()) }} clients</span>{{ $clients->links() }}</div>
            @else
                <div class="client-empty">
                    <strong>{{ $filtersActive ? 'No clients match these filters' : 'No clients yet' }}</strong>
                    <span>{{ $filtersActive ? 'Clear or adjust the filters to see more results.' : 'Use New Client to begin the first guided setup.' }}</span>
                    @if ($filtersActive)<button class="client-reset" type="button" wire:click="resetFilters" style="margin-top: 1rem;">Clear filters</button>@endif
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
