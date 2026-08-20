<x-filament-panels::page>
    @php
        $stats = $this->clientRegistryStats();
        $workflow = $this->clientRegistryWorkflow();
        $clientManagementUrl = \App\Filament\Saas\Pages\ClientManagement::getUrl();
    @endphp

    <style>
        .client-registry { display: grid; gap: var(--pwdl-space-xl, 1.5rem); }
        .client-registry-hero { border: 1px solid var(--pwdl-border-subtle, #e2e8f0); border-radius: var(--pwdl-radius-lg, 12px); background: var(--pwdl-surface-card, #fff); box-shadow: var(--pwdl-shadow-card, 0 1px 2px rgba(15,23,42,.06)); padding: var(--pwdl-space-xl, 1.5rem); }
        .client-registry-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .client-registry-eyebrow { display: inline-flex; color: var(--pwdl-brand-primary, #0f766e); font-size: .75rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
        .client-registry-title { margin: .6rem 0 .35rem; color: var(--pwdl-text-primary, #0f172a); font-size: 1.5rem; line-height: 1.2; font-weight: 850; }
        .client-registry-copy { margin: 0; max-width: 62rem; color: var(--pwdl-text-secondary, #475569); font-size: .9rem; line-height: 1.65; }
        .client-registry-action { display: inline-flex; align-items: center; justify-content: center; min-height: 2.45rem; border-radius: var(--pwdl-radius-md, 8px); background: var(--pwdl-brand-primary, #0f766e); color: #fff; font-size: .82rem; font-weight: 850; padding: .65rem .95rem; text-decoration: none; }
        .client-registry-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--pwdl-space-lg, 1rem); }
        .client-registry-card { border: 1px solid var(--pwdl-border-subtle, #e2e8f0); border-radius: var(--pwdl-radius-lg, 12px); background: var(--pwdl-surface-card, #fff); box-shadow: var(--pwdl-shadow-card, 0 1px 2px rgba(15,23,42,.06)); padding: var(--pwdl-space-lg, 1rem); }
        .client-registry-label { display: inline-flex; margin-bottom: .55rem; color: var(--pwdl-text-muted, #64748b); font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .client-registry-value { color: var(--pwdl-text-primary, #0f172a); font-size: 1.35rem; font-weight: 850; line-height: 1; }
        .client-registry-card-title { margin: 0; color: var(--pwdl-text-primary, #0f172a); font-size: .95rem; font-weight: 850; line-height: 1.35; }
        .client-registry-card-copy { margin: .35rem 0 0; color: var(--pwdl-text-secondary, #475569); font-size: .82rem; line-height: 1.55; }
        .client-registry-table-shell { border: 1px solid var(--pwdl-border-subtle, #e2e8f0); border-radius: var(--pwdl-radius-lg, 12px); background: var(--pwdl-surface-card, #fff); box-shadow: var(--pwdl-shadow-card, 0 1px 2px rgba(15,23,42,.06)); overflow: hidden; }
        .client-registry-table-head { padding: var(--pwdl-space-lg, 1rem); border-bottom: 1px solid var(--pwdl-border-subtle, #e2e8f0); }
        .client-registry-table-body { padding: var(--pwdl-space-lg, 1rem); }
        @media (max-width: 1280px) { .client-registry-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 768px) { .client-registry-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="client-registry">
        <section class="client-registry-hero">
            <div class="client-registry-top">
                <div>
                    <span class="client-registry-eyebrow">Client Registry</span>
                    <h1 class="client-registry-title">Organizations Are Clients</h1>
                    <p class="client-registry-copy">
                        Use this view to manage existing client records. New client setup should start in Client Management, where the workflow captures client type, verification model, clinics, ownership, and service setup together.
                    </p>
                </div>
                <a class="client-registry-action" href="{{ $clientManagementUrl }}">Open Client Management</a>
            </div>
        </section>

        <section class="client-registry-grid" aria-label="Client registry summary">
            <div class="client-registry-card">
                <span class="client-registry-label">Clients</span>
                <div class="client-registry-value">{{ number_format($stats['organizations']) }}</div>
                <p class="client-registry-card-copy">{{ number_format($stats['active_organizations']) }} active organizations</p>
            </div>
            <div class="client-registry-card">
                <span class="client-registry-label">Structure</span>
                <div class="client-registry-value">{{ number_format($stats['clinics']) }}</div>
                <p class="client-registry-card-copy">{{ number_format($stats['dsos']) }} DSOs, {{ number_format($stats['locations']) }} locations</p>
            </div>
            <div class="client-registry-card">
                <span class="client-registry-label">Managed</span>
                <div class="client-registry-value">{{ number_format($stats['managed_clients']) }}</div>
                <p class="client-registry-card-copy">clients using active managed services</p>
            </div>
            <div class="client-registry-card">
                <span class="client-registry-label">Hybrid / Self</span>
                <div class="client-registry-value">{{ number_format($stats['hybrid_or_requested'] + $stats['self_service']) }}</div>
                <p class="client-registry-card-copy">{{ number_format($stats['hybrid_or_requested']) }} hybrid requested, {{ number_format($stats['self_service']) }} self-service</p>
            </div>
        </section>

        <section class="client-registry-grid" aria-label="Client workflow">
            @foreach ($workflow as $item)
                <div class="client-registry-card">
                    <span class="client-registry-label">Workflow</span>
                    <h2 class="client-registry-card-title">{{ $item['label'] }}</h2>
                    <p class="client-registry-card-copy">{{ $item['description'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="client-registry-table-shell">
            <div class="client-registry-table-head">
                <span class="client-registry-label">Existing clients</span>
                <h2 class="client-registry-card-title">Organization Records</h2>
                <p class="client-registry-card-copy">The table remains the system registry for client parent records. Open a record to manage its clinics and organization details.</p>
            </div>
            <div class="client-registry-table-body">
                {{ $this->table }}
            </div>
        </section>
    </div>
</x-filament-panels::page>
