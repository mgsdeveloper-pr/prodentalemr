<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $tabs = $this->tabs();
        $kpis = $this->kpis();
        $clinics = $this->clinics();
        $providers = $this->providers();
        $quickActions = $this->quickActions();
        $supportAccess = $this->supportAccessSummary();
        $activeTab = $this->activeTab();
    @endphp

    <style>
        .org-workspace { display: grid; gap: var(--pwdl-space-xl, 1.5rem); }
        .org-hero, .org-card { border: 1px solid var(--pwdl-border-subtle, #e2e8f0); border-radius: var(--pwdl-radius-lg, 12px); background: var(--pwdl-surface-card, #fff); box-shadow: var(--pwdl-shadow-card, 0 1px 2px rgba(15,23,42,.06)); }
        .org-hero { overflow: hidden; }
        .org-hero-main { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: var(--pwdl-space-xl, 1.5rem); border-bottom: 1px solid var(--pwdl-border-subtle, #e2e8f0); flex-wrap: wrap; }
        .org-eyebrow { color: var(--pwdl-brand-primary, #0f766e); font-size: .74rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
        .org-title { margin: .45rem 0 .35rem; color: var(--pwdl-text-primary, #0f172a); font-size: 1.55rem; line-height: 1.2; font-weight: 850; }
        .org-copy { margin: 0; color: var(--pwdl-text-secondary, #475569); font-size: .86rem; line-height: 1.6; }
        .org-status { display: inline-flex; align-items: center; min-height: 2rem; border-radius: 999px; background: color-mix(in srgb, var(--pwdl-brand-primary, #0f766e) 10%, #fff); color: var(--pwdl-brand-primary, #0f766e); font-size: .78rem; font-weight: 850; padding: .35rem .7rem; }
        .org-tabs { display: flex; gap: .35rem; padding: .75rem 1rem; overflow-x: auto; }
        .org-tab { display: inline-flex; align-items: center; min-height: 2.25rem; border-radius: var(--pwdl-radius-md, 8px); color: var(--pwdl-text-secondary, #475569); font-size: .82rem; font-weight: 800; padding: .5rem .75rem; text-decoration: none; white-space: nowrap; }
        .org-tab--active { background: color-mix(in srgb, var(--pwdl-brand-primary, #0f766e) 10%, #fff); color: var(--pwdl-brand-primary, #0f766e); }
        .org-layout { display: grid; grid-template-columns: 15rem minmax(0, 1fr) 18rem; gap: var(--pwdl-space-lg, 1rem); align-items: start; }
        .org-card { padding: var(--pwdl-space-lg, 1rem); }
        .org-label { display: inline-flex; margin-bottom: .55rem; color: var(--pwdl-text-muted, #64748b); font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .org-card-title { margin: 0; color: var(--pwdl-text-primary, #0f172a); font-size: .96rem; line-height: 1.35; font-weight: 850; }
        .org-card-copy { margin: .35rem 0 0; color: var(--pwdl-text-secondary, #475569); font-size: .82rem; line-height: 1.55; }
        .org-stack { display: grid; gap: .75rem; }
        .org-fact { display: grid; gap: .15rem; padding-bottom: .65rem; border-bottom: 1px solid var(--pwdl-border-subtle, #e2e8f0); }
        .org-fact:last-child { border-bottom: 0; padding-bottom: 0; }
        .org-fact span { color: var(--pwdl-text-muted, #64748b); font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        .org-fact strong { color: var(--pwdl-text-primary, #0f172a); font-size: .84rem; }
        .org-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: var(--pwdl-space-lg, 1rem); }
        .org-kpi-value { color: var(--pwdl-text-primary, #0f172a); font-size: 1.35rem; font-weight: 850; line-height: 1; }
        .org-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .org-table th { color: var(--pwdl-text-muted, #64748b); font-size: .72rem; letter-spacing: .06em; text-align: left; text-transform: uppercase; }
        .org-table th, .org-table td { border-bottom: 1px solid var(--pwdl-border-subtle, #e2e8f0); padding: .65rem .45rem; }
        .org-table tr:last-child td { border-bottom: 0; }
        .org-link { color: var(--pwdl-brand-primary, #0f766e); font-weight: 850; text-decoration: none; }
        .org-action { display: block; color: inherit; text-decoration: none; border: 1px solid var(--pwdl-border-subtle, #e2e8f0); border-radius: var(--pwdl-radius-md, 8px); padding: .75rem; }
        .org-empty { border: 1px dashed var(--pwdl-border-subtle, #e2e8f0); border-radius: var(--pwdl-radius-md, 8px); padding: 1rem; color: var(--pwdl-text-secondary, #475569); font-size: .84rem; }
        .org-support { border-color: #fed7aa; background: #fff7ed; }
        .org-support strong, .org-support .org-label { color: #9a3412; }
        @media (max-width: 1280px) { .org-layout { grid-template-columns: 1fr; } .org-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 768px) { .org-kpi-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="org-workspace">
        <section class="org-hero">
            <div class="org-hero-main">
                <div>
                    <div class="org-eyebrow">Administration > Clients > {{ $this->organization->name }}</div>
                    <h1 class="org-title">{{ $this->organization->name }}</h1>
                    <p class="org-copy">{{ $summary['client_type'] }} · {{ $summary['verification_model'] }} · {{ number_format($this->organization->clinics_count) }} clinic(s)</p>
                </div>
                <span class="org-status">{{ $summary['status'] }}</span>
            </div>
            <nav class="org-tabs" aria-label="Organization workspace sections">
                @foreach ($tabs as $tab)
                    <a class="org-tab {{ $tab['active'] ? 'org-tab--active' : '' }}" href="{{ $tab['url'] }}">{{ $tab['label'] }}</a>
                @endforeach
            </nav>
        </section>

        <section class="org-layout">
            <aside class="org-card">
                <span class="org-label">Organization</span>
                <div class="org-stack">
                    <div class="org-fact"><span>Lifecycle</span><strong>{{ $summary['lifecycle'] }}</strong></div>
                    <div class="org-fact"><span>Onboarding</span><strong>{{ $summary['onboarding'] }}</strong></div>
                    <div class="org-fact"><span>Plan</span><strong>{{ $summary['subscription'] }}</strong></div>
                    <div class="org-fact"><span>Subscription</span><strong>{{ $summary['subscription_status'] }}</strong></div>
                    <div class="org-fact"><span>Manager</span><strong>{{ $summary['manager'] }}</strong></div>
                </div>
            </aside>

            <main class="org-stack">
                @if ($activeTab === 'overview')
                    <section class="org-kpi-grid" aria-label="Organization KPIs">
                        @foreach ($kpis as $kpi)
                            <div class="org-card">
                                <span class="org-label">{{ $kpi['label'] }}</span>
                                <div class="org-kpi-value">{{ number_format($kpi['value']) }}</div>
                                <p class="org-card-copy">{{ $kpi['meta'] }}</p>
                            </div>
                        @endforeach
                    </section>

                    <section class="org-card">
                        <span class="org-label">Overview</span>
                        <h2 class="org-card-title">Client operations summary</h2>
                        <p class="org-card-copy">This workspace is the operational home for the client. Clinics own daily verification work; the organization owns plan, policy, billing, reporting, and oversight.</p>
                    </section>
                @elseif ($activeTab === 'clinics')
                    <section class="org-card">
                        <span class="org-label">Clinics</span>
                        <h2 class="org-card-title">Clinic operating locations</h2>
                        @if (count($clinics))
                            <table class="org-table" aria-label="Organization clinics">
                                <thead>
                                    <tr>
                                        <th>Clinic</th>
                                        <th>Status</th>
                                        <th>Verification</th>
                                        <th>Locations</th>
                                        <th>Users</th>
                                        <th>Providers</th>
                                        <th>Queue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($clinics as $clinic)
                                        <tr>
                                            <td><a class="org-link" href="{{ $clinic['url'] }}">{{ $clinic['name'] }}</a></td>
                                            <td>{{ $clinic['status'] }}</td>
                                            <td>{{ $clinic['verification_model'] }}</td>
                                            <td>{{ number_format($clinic['locations']) }}</td>
                                            <td>{{ number_format($clinic['users']) }}</td>
                                            <td>{{ number_format($clinic['providers']) }}</td>
                                            <td>{{ number_format($clinic['work_items']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="org-empty">No clinics are attached to this client yet.</div>
                        @endif
                    </section>
                @elseif ($activeTab === 'providers')
                    <section class="org-card">
                        <span class="org-label">Provider Support</span>
                        <h2 class="org-card-title">Clinic-owned provider records</h2>
                        <p class="org-card-copy">Providers are owned by the clinic. SaaS access here is for implementation and support inside this client boundary.</p>
                        @if (count($providers))
                            <table class="org-table" aria-label="Organization providers" style="margin-top: .9rem;">
                                <thead>
                                    <tr>
                                        <th>Provider</th>
                                        <th>Clinic</th>
                                        <th>Location</th>
                                        <th>Specialty</th>
                                        <th>NPI</th>
                                        <th>Visits</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($providers as $provider)
                                        <tr>
                                            <td>{{ $provider['name'] }}</td>
                                            <td>{{ $provider['clinic'] }}</td>
                                            <td>{{ $provider['location'] }}</td>
                                            <td>{{ $provider['specialization'] }}</td>
                                            <td>{{ $provider['npi'] }}</td>
                                            <td>{{ number_format($provider['visits']) }}</td>
                                            <td>{{ $provider['status'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="org-empty" style="margin-top: .9rem;">No provider records are attached to this client yet. Normal provider setup should happen from the Clinic workspace.</div>
                        @endif
                    </section>
                @else
                    <section class="org-card">
                        <span class="org-label">{{ str($activeTab)->headline() }}</span>
                        <h2 class="org-card-title">{{ str($activeTab)->headline() }} workspace</h2>
                        <p class="org-card-copy">This section is reserved for contextual {{ str($activeTab)->lower() }} management inside the client workspace. Existing resources remain available through the quick actions while we migrate away from top-level CRUD screens.</p>
                    </section>
                @endif
            </main>

            <aside class="org-card">
                <span class="org-label">Awareness</span>
                <h2 class="org-card-title">Quick actions</h2>
                <div class="org-card org-support" style="margin-top: .8rem; padding: .8rem;">
                    <span class="org-label">Support Access</span>
                    <h3 class="org-card-title">{{ $supportAccess['title'] }}</h3>
                    <p class="org-card-copy">{{ $supportAccess['reason'] }}</p>
                    @if ($supportAccess['active'])
                        <div class="org-stack" style="gap: .35rem; margin-top: .65rem;">
                            <div class="org-fact"><span>Client</span><strong>{{ $supportAccess['organization'] }}</strong></div>
                            <div class="org-fact"><span>Scope</span><strong>{{ $supportAccess['clinic'] }}</strong></div>
                            <div class="org-fact"><span>Started</span><strong>{{ $supportAccess['started_at'] }}</strong></div>
                        </div>
                    @endif
                    <a class="org-link" href="{{ $supportAccess['audit_url'] }}" style="display: inline-flex; margin-top: .7rem;">View audit trail</a>
                </div>
                <div class="org-stack" style="margin-top: .8rem;">
                    @foreach ($quickActions as $action)
                        <a class="org-action" href="{{ $action['url'] }}">
                            <strong class="org-card-title">{{ $action['label'] }}</strong>
                            <p class="org-card-copy">{{ $action['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </aside>
        </section>
    </div>
</x-filament-panels::page>
