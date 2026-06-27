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
        @media (max-width: 1100px) {
            .ada-import-hero,
            .ada-import-grid { grid-template-columns:1fr; }
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
                <h1 class="ada-import-title">Import ADA/CDT codes cleanly.</h1>
                <p class="ada-import-copy">
                    Upload a CSV or Excel file using just <strong>Code</strong> and <strong>Description</strong>. Duplicate codes are skipped automatically so your master library stays clean without extra work.
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
