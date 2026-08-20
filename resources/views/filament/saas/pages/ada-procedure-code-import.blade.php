<x-filament-panels::page>
    <style>
        .ada-import-shell { display:grid; gap:22px; }
        .ada-import-hero { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(320px,.95fr); gap:22px; border:1px solid #dbe4ee; border-radius:28px; background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%); box-shadow:0 22px 48px rgba(15,23,42,.08); padding:28px; }
        .ada-import-eyebrow { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:999px; border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:900; letter-spacing:.14em; text-transform:uppercase; }
        .ada-import-title { margin:14px 0 10px; color:#0f172a; font-size:42px; line-height:1.05; font-weight:950; max-width:14ch; }
        .ada-import-copy { margin:0; color:#64748b; font-size:16px; line-height:1.75; max-width:60ch; }
        .ada-import-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .ada-stat-card { border:1px solid #dbe4ee; border-radius:22px; background:#fff; padding:18px 18px 16px; box-shadow:0 14px 32px rgba(15,23,42,.06); }
        .ada-stat-label { margin:0 0 10px; color:#64748b; font-size:12px; font-weight:900; letter-spacing:.15em; text-transform:uppercase; }
        .ada-stat-value { margin:0; color:#0f172a; font-size:34px; line-height:1; font-weight:950; }
        .ada-stat-note { margin:10px 0 0; color:#64748b; font-size:13px; line-height:1.55; }
        .ada-import-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(340px,.8fr); gap:22px; align-items:start; }
        .ada-card { border:1px solid #dbe4ee; border-radius:28px; background:#fff; box-shadow:0 20px 44px rgba(15,23,42,.07); overflow:hidden; }
        .ada-card-head { padding:18px 22px; border-bottom:1px solid #e5edf5; }
        .ada-card-eyebrow { margin:0 0 6px; color:#0f766e; font-size:12px; font-weight:900; letter-spacing:.16em; text-transform:uppercase; }
        .ada-card-title { margin:0; color:#0f172a; font-size:18px; font-weight:900; }
        .ada-card-copy { margin:8px 0 0; color:#64748b; font-size:14px; line-height:1.7; }
        .ada-card-body { padding:22px; }
        .ada-result-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
        .ada-result-box { border:1px solid #dbe4ee; border-radius:20px; background:#f8fbff; padding:16px; }
        .ada-result-box strong { display:block; color:#0f172a; font-size:28px; line-height:1; font-weight:950; }
        .ada-result-box span { display:block; margin-top:8px; color:#64748b; font-size:12px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        .ada-result-list { display:grid; gap:10px; max-height:520px; overflow:auto; padding-right:4px; }
        .ada-result-item { border:1px solid #dbe4ee; border-radius:18px; padding:14px 16px; background:#fff; }
        .ada-result-item[data-status="ready"],
        .ada-result-item[data-status="imported"] { background:#f0fdf4; border-color:#bbf7d0; }
        .ada-result-item[data-status="skipped"] { background:#fff7ed; border-color:#fed7aa; }
        .ada-result-item[data-status="failed"] { background:#fef2f2; border-color:#fecaca; }
        .ada-result-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .ada-result-code { margin:0; color:#0f172a; font-size:15px; font-weight:900; }
        .ada-result-badge { display:inline-flex; align-items:center; gap:7px; border-radius:999px; padding:6px 10px; font-size:11px; font-weight:900; letter-spacing:.12em; text-transform:uppercase; }
        .ada-result-desc { margin:8px 0 0; color:#334155; font-size:14px; line-height:1.65; }
        .ada-result-msg { margin:8px 0 0; color:#64748b; font-size:13px; line-height:1.55; }
        .ada-latest-list { display:grid; gap:10px; }
        .ada-latest-item { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; border:1px solid #dbe4ee; border-radius:18px; background:#f8fbff; padding:14px 16px; }
        .ada-latest-code { margin:0; color:#0f172a; font-size:14px; font-weight:900; }
        .ada-latest-desc { margin:6px 0 0; color:#64748b; font-size:13px; line-height:1.6; }
        .ada-empty { border:1px dashed #cbd5e1; border-radius:18px; padding:18px; color:#64748b; font-size:14px; line-height:1.7; background:#fff; }
        .ada-inline-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:18px; flex-wrap:wrap; }
        .ada-management-grid { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(320px,.75fr); gap:22px; align-items:start; }
        .ada-tools { display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
        .ada-search { min-width:280px; flex:1; border:1px solid #cbd5e1; border-radius:14px; padding:10px 12px; color:#0f172a; font-size:14px; outline:none; }
        .ada-filter-row { display:flex; gap:8px; flex-wrap:wrap; }
        .ada-filter-button { border:1px solid #dbe4ee; border-radius:999px; background:#fff; color:#334155; padding:8px 12px; font-size:12px; font-weight:900; cursor:pointer; }
        .ada-filter-button[data-active="true"] { background:#0f766e; border-color:#0f766e; color:#fff; }
        .ada-table-wrap { overflow:auto; border:1px solid #dbe4ee; border-radius:18px; }
        .ada-table { width:100%; border-collapse:collapse; min-width:860px; }
        .ada-table th { background:#f8fbff; color:#64748b; font-size:11px; font-weight:900; letter-spacing:.14em; text-transform:uppercase; text-align:left; padding:12px 14px; border-bottom:1px solid #dbe4ee; }
        .ada-table td { color:#0f172a; font-size:13px; line-height:1.45; padding:12px 14px; border-bottom:1px solid #edf2f7; vertical-align:top; }
        .ada-table tr:last-child td { border-bottom:0; }
        .ada-status { display:inline-flex; align-items:center; border-radius:999px; padding:5px 9px; font-size:11px; font-weight:900; white-space:nowrap; }
        .ada-status[data-status="active"] { background:#dcfce7; color:#166534; }
        .ada-status[data-status="inactive"] { background:#f1f5f9; color:#475569; }
        .ada-status[data-status="deprecated"] { background:#fef3c7; color:#92400e; }
        .ada-status[data-status="removed_by_ada"] { background:#fee2e2; color:#991b1b; }
        .ada-link-button { border:0; background:transparent; color:#0f766e; font-size:12px; font-weight:900; cursor:pointer; padding:0; }
        .ada-audit-list { display:grid; gap:12px; }
        .ada-audit-item { border:1px solid #dbe4ee; border-radius:16px; padding:12px 14px; background:#f8fbff; }
        .ada-audit-title { margin:0; color:#0f172a; font-size:13px; font-weight:900; }
        .ada-audit-meta { margin:6px 0 0; color:#64748b; font-size:12px; line-height:1.5; }
        .ada-audit-note { margin:8px 0 0; color:#334155; font-size:13px; line-height:1.5; }
        @media (max-width: 1100px) {
            .ada-import-hero,
            .ada-import-grid,
            .ada-management-grid { grid-template-columns:1fr; }
            .ada-import-title { max-width:none; font-size:34px; }
        }
        @media (max-width: 720px) {
            .ada-import-hero { padding:22px; }
            .ada-result-grid,
            .ada-import-stats { grid-template-columns:1fr; }
        }
    </style>

    <div class="ada-import-shell">
        <section class="ada-import-hero">
            <div>
                <span class="ada-import-eyebrow">Master Code Library</span>
                <h1 class="ada-import-title">Govern ADA/CDT codes cleanly.</h1>
                <p class="ada-import-copy">
                    Upload official additions in bulk, or use governed actions for single-code additions, updates, and ADA removals. Removed codes stay available for history but disappear from active user pickers.
                </p>
            </div>

            <div class="ada-import-stats">
                <article class="ada-stat-card">
                    <p class="ada-stat-label">Total codes</p>
                    <p class="ada-stat-value">{{ number_format($this->getTotalCodeCount()) }}</p>
                    <p class="ada-stat-note">All ADA/CDT codes currently stored in the central library.</p>
                </article>

                <article class="ada-stat-card">
                    <p class="ada-stat-label">Active codes</p>
                    <p class="ada-stat-value">{{ number_format($this->getActiveCodeCount()) }}</p>
                    <p class="ada-stat-note">These active codes are available for clinic and verification template builders.</p>
                </article>
            </div>
        </section>

        <section class="ada-management-grid">
            <div class="ada-card">
                <div class="ada-card-head">
                    <p class="ada-card-eyebrow">Code management</p>
                    <h2 class="ada-card-title">Manage master code lifecycle</h2>
                    <p class="ada-card-copy">Search the library, review lifecycle state, and confirm which codes remain visible to template builders.</p>
                </div>

                <div class="ada-card-body">
                    @php
                        $counts = $this->getLifecycleCounts();
                        $filters = [
                            \App\Models\AdaProcedureCode::LIFECYCLE_ACTIVE => 'Active',
                            \App\Models\AdaProcedureCode::LIFECYCLE_INACTIVE => 'Inactive',
                            \App\Models\AdaProcedureCode::LIFECYCLE_DEPRECATED => 'Deprecated',
                            \App\Models\AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA => 'Removed by ADA',
                            'all' => 'All',
                        ];
                    @endphp

                    <div class="ada-tools">
                        <input class="ada-search" type="search" wire:model.live.debounce.400ms="codeSearch" placeholder="Search code, description, class, or source">
                        <div class="ada-filter-row">
                            @foreach($filters as $filterKey => $filterLabel)
                                <button class="ada-filter-button" type="button" wire:click="$set('lifecycleFilter', '{{ $filterKey }}')" data-active="{{ $lifecycleFilter === $filterKey ? 'true' : 'false' }}">
                                    {{ $filterLabel }} {{ number_format($counts[$filterKey] ?? 0) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="ada-table-wrap" style="margin-top:16px;">
                        <table class="ada-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Source</th>
                                    <th>Reviewed</th>
                                    <th>Audit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->getManagedCodes() as $code)
                                    @php
                                        $status = $code->lifecycle_status ?: \App\Models\AdaProcedureCode::LIFECYCLE_ACTIVE;
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $code->procedure_code }}</strong></td>
                                        <td>{{ $code->description }}</td>
                                        <td>{{ $code->class ?: '-' }}</td>
                                        <td>
                                            <span class="ada-status" data-status="{{ $status }}">
                                                {{ \App\Models\AdaProcedureCode::LIFECYCLE_OPTIONS[$status] ?? 'Active' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $code->source_document ?: '-' }}
                                            @if($code->source_year)
                                                <div style="color:#64748b;">{{ $code->source_year }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $code->last_reviewed_at?->format('M d, Y') ?: '-' }}
                                        </td>
                                        <td>
                                            <button type="button" class="ada-link-button" wire:click="selectAuditCode({{ $code->id }})">
                                                View audit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">No ADA/CDT codes match the current filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <aside class="ada-card">
                <div class="ada-card-head">
                    <p class="ada-card-eyebrow">Audit history</p>
                    <h2 class="ada-card-title">
                        @if($this->getSelectedAuditCode())
                            {{ $this->getSelectedAuditCode()->procedure_code }}
                        @else
                            Select a code
                        @endif
                    </h2>
                    <p class="ada-card-copy">Manual add, update, and ADA removal events are tracked with source and reason.</p>
                </div>

                <div class="ada-card-body">
                    @if($this->getSelectedAuditCode())
                        <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
                            <button type="button" class="ada-link-button" wire:click="clearAuditCode">Clear</button>
                        </div>

                        <div class="ada-audit-list">
                            @forelse($this->getSelectedCodeAuditEntries() as $entry)
                                <article class="ada-audit-item">
                                    <p class="ada-audit-title">{{ str($entry->event_type)->replace('_', ' ')->headline() }}</p>
                                    <p class="ada-audit-meta">
                                        {{ $entry->created_at?->format('M d, Y h:i A') }} by {{ $entry->actorUser?->name ?? 'System' }}
                                    </p>
                                    @if(filled($entry->notes))
                                        <p class="ada-audit-note">{{ $entry->notes }}</p>
                                    @endif
                                </article>
                            @empty
                                <div class="ada-empty">No audit events recorded for this code yet.</div>
                            @endforelse
                        </div>
                    @else
                        <div class="ada-empty">Choose “View audit” from the code table to inspect change history for a code.</div>
                    @endif
                </div>
            </aside>
        </section>

        <div class="ada-import-grid">
            <section class="ada-card">
                <div class="ada-card-head">
                    <p class="ada-card-eyebrow">Import file</p>
                    <h2 class="ada-card-title">Upload code list</h2>
                    <p class="ada-card-copy">Only the important fields are required: <strong>Code</strong> and <strong>Description</strong>. Optional <strong>Class</strong> will also be captured if provided.</p>
                </div>

                <div class="ada-card-body">
                    <form wire:submit.prevent="importCodes">
                        {{ $this->form }}

                        <div class="ada-inline-actions">
                            <x-filament::button type="button" color="gray" tag="a" href="{{ url('/samples/ada-cdt-import-sample.csv') }}" target="_blank">
                                Download sample
                            </x-filament::button>

                            <x-filament::button type="button" color="gray" wire:click="previewCodes">
                                Preview import
                            </x-filament::button>

                            <x-filament::button type="submit">
                                Import ADA/CDT codes
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="ada-card">
                <div class="ada-card-head">
                    <p class="ada-card-eyebrow">Latest records</p>
                    <h2 class="ada-card-title">Recently added codes</h2>
                    <p class="ada-card-copy">A quick glance at the newest master codes available to your templates.</p>
                </div>

                <div class="ada-card-body">
                    <div class="ada-latest-list">
                        @forelse($this->getLatestCodes() as $code)
                            <article class="ada-latest-item">
                                <div>
                                    <p class="ada-latest-code">{{ $code->procedure_code }}</p>
                                    <p class="ada-latest-desc">{{ $code->description }}</p>
                                    <p class="ada-latest-desc" style="margin-top:4px;">
                                        {{ \App\Models\AdaProcedureCode::LIFECYCLE_OPTIONS[$code->lifecycle_status ?: \App\Models\AdaProcedureCode::LIFECYCLE_ACTIVE] ?? 'Active' }}
                                    </p>
                                </div>
                                @if($code->class_tokens !== [])
                                    <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                        @foreach($code->class_tokens as $classToken)
                                            <x-filament::badge color="gray">{{ $classToken }}</x-filament::badge>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="ada-empty">No ADA/CDT codes have been added yet.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        @php
            $result = $previewResult ?? $lastImportResult;
        @endphp

        @if($result)
            <section class="ada-card">
                <div class="ada-card-head">
                    <p class="ada-card-eyebrow">{{ $previewResult ? 'Preview result' : 'Import result' }}</p>
                    <h2 class="ada-card-title">{{ $previewResult ? 'Review what will happen before import' : 'Import summary' }}</h2>
                    <p class="ada-card-copy">
                        {{ $previewResult
                            ? 'Ready rows will be imported, duplicate codes will be skipped, and invalid rows need correction.'
                            : 'Imported rows were added successfully. Duplicate codes were skipped and invalid rows need correction in the source file.' }}
                    </p>
                </div>

                <div class="ada-card-body">
                    <div class="ada-result-grid">
                        @if($previewResult)
                            <div class="ada-result-box">
                                <strong>{{ number_format($result['ready'] ?? 0) }}</strong>
                                <span>Ready</span>
                            </div>
                        @else
                            <div class="ada-result-box">
                                <strong>{{ number_format($result['imported'] ?? 0) }}</strong>
                                <span>Imported</span>
                            </div>
                        @endif

                        <div class="ada-result-box">
                            <strong>{{ number_format($result['skipped'] ?? 0) }}</strong>
                            <span>Duplicate skipped</span>
                        </div>

                        <div class="ada-result-box">
                            <strong>{{ number_format($result['failed'] ?? 0) }}</strong>
                            <span>Invalid rows</span>
                        </div>
                    </div>

                    <div class="ada-result-list">
                        @foreach(($result['row_results'] ?? []) as $row)
                            @php
                                $status = $row['status'] ?? 'ready';
                                $badgeStyles = match ($status) {
                                    'ready', 'imported' => 'background:#dcfce7;color:#166534;',
                                    'skipped' => 'background:#ffedd5;color:#c2410c;',
                                    default => 'background:#fee2e2;color:#b91c1c;',
                                };
                            @endphp

                            <article class="ada-result-item" data-status="{{ $status }}">
                                <div class="ada-result-top">
                                    <p class="ada-result-code">Row {{ $row['row'] ?? '-' }} · {{ $row['code'] ?? 'Missing code' }}</p>
                                    <span class="ada-result-badge" style="{{ $badgeStyles }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </div>

                                <p class="ada-result-desc">{{ $row['description'] ?? '-' }}</p>
                                <p class="ada-result-msg">{{ $row['message'] ?? '' }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
