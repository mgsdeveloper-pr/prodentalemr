<x-filament-panels::page>
    <style>
        .ada-workspace { display:grid; gap:18px; }
        .ada-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); border:1px solid #dbe4ee; border-radius:8px; background:#fff; overflow:hidden; }
        .ada-summary-item { min-width:0; padding:16px 18px; border-right:1px solid #e5edf5; }
        .ada-summary-item:last-child { border-right:0; }
        .ada-summary-label { margin:0 0 6px; color:#64748b; font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .ada-summary-value { margin:0; color:#0f172a; font-size:22px; line-height:1.1; font-weight:800; }
        .ada-summary-note { margin:5px 0 0; color:#64748b; font-size:12px; line-height:1.45; }
        .ada-panel { border:1px solid #dbe4ee; border-radius:8px; background:#fff; overflow:hidden; }
        .ada-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:16px 18px; border-bottom:1px solid #e5edf5; }
        .ada-panel-title { margin:0; color:#0f172a; font-size:17px; font-weight:800; }
        .ada-panel-copy { margin:5px 0 0; color:#64748b; font-size:13px; line-height:1.55; }
        .ada-panel-body { padding:18px; }
        .ada-close { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #dbe4ee; border-radius:7px; background:#fff; color:#475569; cursor:pointer; font-size:18px; }
        .ada-tools { display:flex; align-items:center; gap:12px; padding:14px 16px; border-bottom:1px solid #e5edf5; flex-wrap:wrap; }
        .ada-search { flex:1 1 320px; min-width:220px; height:40px; border:1px solid #cbd5e1; border-radius:7px; padding:0 12px; color:#0f172a; font-size:13px; outline:none; }
        .ada-search:focus { border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.1); }
        .ada-filters { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
        .ada-filter { min-height:36px; border:1px solid #dbe4ee; border-radius:7px; background:#fff; color:#334155; padding:7px 10px; font-size:12px; font-weight:700; cursor:pointer; }
        .ada-filter[data-active="true"] { border-color:#0f766e; background:#e8f7f4; color:#0f766e; }
        .ada-table-wrap { overflow:auto; }
        .ada-table { width:100%; min-width:900px; border-collapse:collapse; }
        .ada-table th { padding:11px 14px; border-bottom:1px solid #dbe4ee; background:#f8fafc; color:#475569; font-size:11px; font-weight:800; letter-spacing:.05em; text-transform:uppercase; text-align:left; }
        .ada-table td { padding:12px 14px; border-bottom:1px solid #edf2f7; color:#334155; font-size:13px; line-height:1.45; vertical-align:top; }
        .ada-table tr:last-child td { border-bottom:0; }
        .ada-code { color:#0f172a; font-weight:800; white-space:nowrap; }
        .ada-status { display:inline-flex; align-items:center; border-radius:999px; padding:5px 9px; font-size:11px; font-weight:800; white-space:nowrap; }
        .ada-status[data-status="active"] { background:#dcfce7; color:#166534; }
        .ada-status[data-status="inactive"] { background:#f1f5f9; color:#475569; }
        .ada-status[data-status="deprecated"] { background:#fef3c7; color:#92400e; }
        .ada-status[data-status="removed_by_ada"] { background:#fee2e2; color:#991b1b; }
        .ada-link { border:0; background:transparent; color:#0f766e; padding:0; font-size:12px; font-weight:800; cursor:pointer; white-space:nowrap; }
        .ada-main-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:18px; align-items:start; }
        .ada-main-grid[data-audit="true"] { grid-template-columns:minmax(0,1fr) 340px; }
        .ada-audit-list { display:grid; gap:10px; }
        .ada-audit-item { border:1px solid #dbe4ee; border-radius:7px; padding:12px; background:#f8fafc; }
        .ada-audit-title { margin:0; color:#0f172a; font-size:13px; font-weight:800; }
        .ada-audit-meta, .ada-audit-note { margin:5px 0 0; color:#64748b; font-size:12px; line-height:1.5; }
        .ada-empty { padding:28px 18px; color:#64748b; font-size:13px; text-align:center; }
        .ada-form-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:16px; flex-wrap:wrap; }
        .ada-results { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:16px; }
        .ada-result { border:1px solid #dbe4ee; border-radius:7px; padding:12px; background:#f8fafc; }
        .ada-result strong { display:block; color:#0f172a; font-size:20px; }
        .ada-result span { display:block; margin-top:4px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; }
        .ada-result-rows { display:grid; gap:8px; max-height:300px; overflow:auto; margin-top:14px; }
        .ada-result-row { display:flex; justify-content:space-between; gap:16px; border:1px solid #e5edf5; border-radius:7px; padding:10px 12px; font-size:12px; }
        @media (max-width:1100px) { .ada-main-grid[data-audit="true"] { grid-template-columns:1fr; } }
        @media (max-width:760px) {
            .ada-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .ada-summary-item:nth-child(2) { border-right:0; }
            .ada-summary-item:nth-child(-n+2) { border-bottom:1px solid #e5edf5; }
            .ada-results { grid-template-columns:1fr; }
        }
    </style>

    @php
        $counts = $this->getLifecycleCounts();
        $selectedCode = $this->getSelectedAuditCode();
        $filters = [
            \App\Models\AdaProcedureCode::LIFECYCLE_ACTIVE => 'Active',
            \App\Models\AdaProcedureCode::LIFECYCLE_INACTIVE => 'Inactive',
            \App\Models\AdaProcedureCode::LIFECYCLE_DEPRECATED => 'Deprecated',
            \App\Models\AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA => 'Removed by ADA',
            'all' => 'All',
        ];
        $result = $previewResult ?? $lastImportResult;
    @endphp

    <div class="ada-workspace">
        <section class="ada-summary" aria-label="ADA CDT code summary">
            <div class="ada-summary-item">
                <p class="ada-summary-label">Total Codes</p>
                <p class="ada-summary-value">{{ number_format($counts['all'] ?? 0) }}</p>
                <p class="ada-summary-note">Central library</p>
            </div>
            <div class="ada-summary-item">
                <p class="ada-summary-label">Active</p>
                <p class="ada-summary-value">{{ number_format($counts[\App\Models\AdaProcedureCode::LIFECYCLE_ACTIVE] ?? 0) }}</p>
                <p class="ada-summary-note">Available to templates</p>
            </div>
            <div class="ada-summary-item">
                <p class="ada-summary-label">Inactive / Deprecated</p>
                <p class="ada-summary-value">{{ number_format(($counts[\App\Models\AdaProcedureCode::LIFECYCLE_INACTIVE] ?? 0) + ($counts[\App\Models\AdaProcedureCode::LIFECYCLE_DEPRECATED] ?? 0)) }}</p>
                <p class="ada-summary-note">Unavailable for new selection</p>
            </div>
            <div class="ada-summary-item">
                <p class="ada-summary-label">Removed by ADA</p>
                <p class="ada-summary-value">{{ number_format($counts[\App\Models\AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA] ?? 0) }}</p>
                <p class="ada-summary-note">Retained for history</p>
            </div>
        </section>

        @if($showImportPanel)
            <section class="ada-panel">
                <div class="ada-panel-head">
                    <div>
                        <h2 class="ada-panel-title">Import ADA/CDT Codes</h2>
                        <p class="ada-panel-copy">Upload approved additions, preview the rows, then import. Existing codes are skipped and historical records are not changed.</p>
                    </div>
                    <button class="ada-close" type="button" wire:click="closeImportPanel" title="Close import">&times;</button>
                </div>
                <div class="ada-panel-body">
                    <form wire:submit.prevent="importCodes">
                        {{ $this->form }}
                        <div class="ada-form-actions">
                            <x-filament::button type="button" color="gray" tag="a" href="{{ url('/samples/ada-cdt-import-sample.csv') }}" target="_blank" icon="heroicon-o-arrow-down-tray">Download Sample</x-filament::button>
                            <x-filament::button type="button" color="gray" wire:click="previewCodes" wire:loading.attr="disabled" wire:target="previewCodes" icon="heroicon-o-eye">
                                <span wire:loading.remove wire:target="previewCodes">Preview Import</span><span wire:loading wire:target="previewCodes">Checking...</span>
                            </x-filament::button>
                            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="importCodes" icon="heroicon-o-arrow-up-tray">
                                <span wire:loading.remove wire:target="importCodes">Import Codes</span><span wire:loading wire:target="importCodes">Importing...</span>
                            </x-filament::button>
                        </div>
                    </form>

                    @if($result)
                        <div class="ada-results">
                            <div class="ada-result"><strong>{{ number_format($previewResult ? ($result['ready'] ?? 0) : ($result['imported'] ?? 0)) }}</strong><span>{{ $previewResult ? 'Ready' : 'Imported' }}</span></div>
                            <div class="ada-result"><strong>{{ number_format($result['skipped'] ?? 0) }}</strong><span>Duplicates Skipped</span></div>
                            <div class="ada-result"><strong>{{ number_format($result['failed'] ?? 0) }}</strong><span>Invalid Rows</span></div>
                        </div>
                        <div class="ada-result-rows">
                            @foreach(($result['row_results'] ?? []) as $row)
                                <div class="ada-result-row"><strong>Row {{ $row['row'] ?? '-' }} · {{ $row['code'] ?? 'Missing code' }}</strong><span>{{ $row['message'] ?? ucfirst($row['status'] ?? '') }}</span></div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="ada-main-grid" data-audit="{{ $selectedCode ? 'true' : 'false' }}">
            <div class="ada-panel">
                <div class="ada-panel-head">
                    <div>
                        <h2 class="ada-panel-title">Code Library</h2>
                        <p class="ada-panel-copy">Search codes, review their current status, and open recorded change history.</p>
                    </div>
                </div>
                <div class="ada-tools">
                    <input class="ada-search" type="search" wire:model.live.debounce.400ms="codeSearch" placeholder="Search code, description, class, or source">
                    <div class="ada-filters">
                        @foreach($filters as $filterKey => $filterLabel)
                            <button class="ada-filter" type="button" wire:click="$set('lifecycleFilter', '{{ $filterKey }}')" data-active="{{ $lifecycleFilter === $filterKey ? 'true' : 'false' }}">{{ $filterLabel }} {{ number_format($counts[$filterKey] ?? 0) }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="ada-table-wrap">
                    <table class="ada-table">
                        <thead><tr><th>Code</th><th>Description</th><th>Class</th><th>Status</th><th>Source</th><th>Reviewed</th><th>History</th></tr></thead>
                        <tbody>
                            @forelse($this->getManagedCodes() as $code)
                                @php($status = $code->lifecycle_status ?: \App\Models\AdaProcedureCode::LIFECYCLE_ACTIVE)
                                <tr>
                                    <td><span class="ada-code">{{ $code->procedure_code }}</span></td>
                                    <td>{{ $code->description }}</td>
                                    <td>{{ $code->class ?: '—' }}</td>
                                    <td><span class="ada-status" data-status="{{ $status }}">{{ \App\Models\AdaProcedureCode::LIFECYCLE_OPTIONS[$status] ?? 'Active' }}</span></td>
                                    <td>{{ $code->source_document ?: '—' }} @if($code->source_year)<div style="color:#64748b;">{{ $code->source_year }}</div>@endif</td>
                                    <td>{{ $code->last_reviewed_at?->format('M d, Y') ?: '—' }}</td>
                                    <td><button type="button" class="ada-link" wire:click="selectAuditCode({{ $code->id }})">View History</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="ada-empty">No ADA/CDT codes match the selected filters.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($selectedCode)
                <aside class="ada-panel">
                    <div class="ada-panel-head">
                        <div><h2 class="ada-panel-title">{{ $selectedCode->procedure_code }} History</h2><p class="ada-panel-copy">Recorded additions, updates, and ADA removals.</p></div>
                        <button class="ada-close" type="button" wire:click="clearAuditCode" title="Close history">&times;</button>
                    </div>
                    <div class="ada-panel-body">
                        <div class="ada-audit-list">
                            @forelse($this->getSelectedCodeAuditEntries() as $entry)
                                <article class="ada-audit-item">
                                    <p class="ada-audit-title">{{ str($entry->event_type)->replace('_', ' ')->headline() }}</p>
                                    <p class="ada-audit-meta">{{ $entry->created_at?->format('M d, Y h:i A') }} by {{ $entry->actorUser?->name ?? 'System' }}</p>
                                    @if(filled($entry->notes))<p class="ada-audit-note">{{ $entry->notes }}</p>@endif
                                </article>
                            @empty
                                <div class="ada-empty">No history has been recorded for this code.</div>
                            @endforelse
                        </div>
                    </div>
                </aside>
            @endif
        </section>
    </div>
</x-filament-panels::page>
