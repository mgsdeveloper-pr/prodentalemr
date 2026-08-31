<x-filament-panels::page>
    @php
        $summary = $this->getUpdateSummary();
        $pending = $summary['pending'];
        $checks = $summary['checks'];
        $run = $summary['run'];
        $history = $summary['history'];
        $isRunning = ($run['status'] ?? null) === 'running';
        $isFailed = ($run['status'] ?? null) === 'failed';
        $initialCount = count($run['initial_migrations'] ?? []);
        $completedCount = count($run['completed_migrations'] ?? []);
        $progress = $initialCount > 0 ? min(100, (int) round(($completedCount / $initialCount) * 82)) : 0;
        if ($isRunning && ($run['phase'] ?? 'migrations') !== 'migrations') {
            $progress = match ($run['phase']) {
                'optimize' => 88,
                'queue' => 94,
                'complete' => 98,
                default => $progress,
            };
        }
        if (($run['status'] ?? null) === 'completed') {
            $progress = 100;
        }
    @endphp

    <style>
        .system-update-shell{display:grid;gap:20px}.system-update-hero,.system-update-card{border:1px solid #dbe4ee;border-radius:8px;background:#fff;overflow:hidden}.system-update-hero{padding:24px 26px;display:flex;justify-content:space-between;gap:24px;align-items:flex-start}.system-update-eyebrow{margin:0 0 8px;color:#0f766e;font-size:12px;font-weight:800;text-transform:uppercase}.system-update-title{margin:0;color:#0f172a;font-size:26px;font-weight:800}.system-update-copy{margin:8px 0 0;color:#64748b;font-size:14px;line-height:1.65;max-width:78ch}.system-update-status{min-width:180px;padding:14px 16px;border:1px solid #ccfbf1;border-radius:8px;background:#f0fdfa}.system-update-status strong{display:block;margin-top:5px;color:#0f172a;font-size:20px}.system-update-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr);gap:20px}.system-update-card-head{padding:18px 20px;border-bottom:1px solid #e5edf5}.system-update-card-head h3{margin:0;color:#0f172a;font-size:18px;font-weight:800}.system-update-card-head p{margin:5px 0 0;color:#64748b;font-size:13px;line-height:1.55}.system-update-card-body{padding:18px 20px}.system-update-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.system-update-check{display:flex;gap:10px;padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}.system-update-dot{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex:0 0 auto;font-size:12px;font-weight:900}.is-pass .system-update-dot{background:#ccfbf1;color:#0f766e}.is-fail .system-update-dot{background:#fee2e2;color:#b91c1c}.system-update-check strong{display:block;color:#0f172a;font-size:13px}.system-update-check span{display:block;margin-top:3px;color:#64748b;font-size:12px;line-height:1.45}.system-update-table{width:100%;border-collapse:collapse}.system-update-table th,.system-update-table td{padding:11px 12px;border-bottom:1px solid #e5edf5;text-align:left;font-size:13px}.system-update-table th{color:#475569;background:#f8fafc;font-size:11px;text-transform:uppercase}.system-update-table td{color:#0f172a}.system-update-empty{padding:24px;text-align:center;color:#64748b}.system-update-form{display:grid;gap:14px}.system-update-field label{display:block;margin-bottom:6px;color:#0f172a;font-size:13px;font-weight:700}.system-update-field input[type=password]{width:100%;height:42px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;background:#fff;color:#0f172a}.system-update-checkbox{display:flex;align-items:flex-start;gap:9px;color:#334155;font-size:13px;line-height:1.5}.system-update-error{margin-top:5px;color:#b91c1c;font-size:12px}.system-update-button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 16px;border:1px solid #0f766e;border-radius:8px;background:#0f766e;color:#fff;font-size:13px;font-weight:800;cursor:pointer}.system-update-button:disabled{opacity:.5;cursor:not-allowed}.system-update-button.secondary{border-color:#cbd5e1;background:#fff;color:#0f172a}.system-update-button.danger{border-color:#dc2626;background:#dc2626}.system-update-alert{padding:12px 14px;border:1px solid #fde68a;border-radius:8px;background:#fffbeb;color:#92400e;font-size:13px;line-height:1.55}.system-update-progress{height:9px;border-radius:999px;background:#e2e8f0;overflow:hidden}.system-update-progress span{display:block;height:100%;background:#0f766e;transition:width .25s ease}.system-update-run-meta{display:flex;justify-content:space-between;gap:12px;margin-top:9px;color:#64748b;font-size:12px}.system-update-history{display:grid;gap:10px}.system-update-history-item{padding:13px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}.system-update-history-item strong{color:#0f172a;font-size:13px}.system-update-history-item div{margin-top:4px;color:#64748b;font-size:12px}.system-update-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;overflow-wrap:anywhere}@media(max-width:1000px){.system-update-grid{grid-template-columns:1fr}.system-update-checks{grid-template-columns:1fr}}@media(max-width:680px){.system-update-hero{flex-direction:column}.system-update-status{width:100%}.system-update-card-body{padding:15px}.system-update-table{display:block;overflow-x:auto}}
    </style>

    <div class="system-update-shell" @if($isRunning) wire:poll.1500ms="continueUpdate" @endif>
        <section class="system-update-hero">
            <div>
                <p class="system-update-eyebrow">Protected Release Tool</p>
                <h2 class="system-update-title">Database and application updates</h2>
                <p class="system-update-copy">Review pending database changes, confirm the live backup, and apply updates in short protected steps designed for shared hosting. No database password, server path, or source content is displayed here.</p>
            </div>
            <div class="system-update-status">
                <span style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;">Database status</span>
                <strong>{{ count($pending) === 0 ? 'Current' : count($pending).' pending' }}</strong>
            </div>
        </section>

        @if($isRunning || $isFailed)
            <section class="system-update-card">
                <div class="system-update-card-head">
                    <h3>{{ $isFailed ? 'Update stopped' : 'Update in progress' }}</h3>
                    <p>{{ $run['message'] ?? 'Preparing update status.' }}</p>
                </div>
                <div class="system-update-card-body">
                    <div class="system-update-progress"><span style="width:{{ $progress }}%"></span></div>
                    <div class="system-update-run-meta">
                        <span>{{ $completedCount }} of {{ $initialCount }} migrations completed</span>
                        <span>Release {{ $run['id'] ?? '-' }}</span>
                    </div>
                    @if(filled($run['current_migration'] ?? null))
                        <p class="system-update-code" style="margin:14px 0 0;color:#0f172a;">Applying: {{ $run['current_migration'] }}</p>
                    @endif
                    @if($isFailed)
                        <div class="system-update-alert" style="margin-top:14px;border-color:#fecaca;background:#fef2f2;color:#991b1b;">{{ $run['error'] ?? 'The update could not continue.' }}</div>
                        <div class="system-update-field" style="margin-top:14px;max-width:420px;">
                            <label for="recovery-password">Confirm SaaS Admin password to restore application access</label>
                            <input id="recovery-password" type="password" wire:model="confirmationPassword" autocomplete="current-password">
                            @error('confirmationPassword') <div class="system-update-error">{{ $message }}</div> @enderror
                        </div>
                        <button type="button" class="system-update-button danger" style="margin-top:12px;" wire:click="restoreApplication" wire:loading.attr="disabled">Restore application access</button>
                    @endif
                </div>
            </section>
        @endif

        <div class="system-update-grid">
            <div style="display:grid;gap:20px;align-content:start;">
                <section class="system-update-card">
                    <div class="system-update-card-head"><h3>Preflight checks</h3><p>Required production safeguards are checked again before an update starts.</p></div>
                    <div class="system-update-card-body">
                        <div class="system-update-checks">
                            @foreach($checks as $check)
                                <div class="system-update-check {{ $check['passed'] ? 'is-pass' : 'is-fail' }}">
                                    <div class="system-update-dot">{{ $check['passed'] ? 'OK' : '!' }}</div>
                                    <div><strong>{{ $check['label'] }}</strong><span>{{ $check['passed'] ? 'Ready' : $check['action'] }}{{ ! $check['blocking'] ? ' (advisory)' : '' }}</span></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="system-update-card">
                    <div class="system-update-card-head"><h3>Pending database changes</h3><p>Changes are applied in the exact order shown below.</p></div>
                    @if(count($pending) > 0)
                        <table class="system-update-table">
                            <thead><tr><th>#</th><th>Migration</th><th>Execution</th></tr></thead>
                            <tbody>
                            @foreach($pending as $index => $migration)
                                <tr><td>{{ $index + 1 }}</td><td class="system-update-code">{{ $migration['name'] }}</td><td>Protected single step</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="system-update-empty"><strong style="display:block;color:#0f172a;">No pending migrations</strong><span style="display:block;margin-top:5px;">The database matches the deployed application code.</span></div>
                    @endif
                </section>
            </div>

            <div style="display:grid;gap:20px;align-content:start;">
                <section class="system-update-card">
                    <div class="system-update-card-head"><h3>Start protected update</h3><p>Restricted to an active SaaS Administrator.</p></div>
                    <div class="system-update-card-body">
                        @if(!app()->environment('production'))
                            <div class="system-update-alert" style="margin-bottom:14px;">This is not the production environment. The screen can be reviewed here, but live production settings must be validated on the server.</div>
                        @endif
                        <div class="system-update-form">
                            <label class="system-update-checkbox"><input type="checkbox" wire:model="backupConfirmed"><span>I confirm that a current database backup was created and its restore file was verified.</span></label>
                            @error('backupConfirmed') <div class="system-update-error">{{ $message }}</div> @enderror
                            <div class="system-update-field">
                                <label for="update-password">Confirm SaaS Admin password</label>
                                <input id="update-password" type="password" wire:model="confirmationPassword" autocomplete="current-password">
                                @error('confirmationPassword') <div class="system-update-error">{{ $message }}</div> @enderror
                            </div>
                            <button type="button" class="system-update-button" wire:click="startUpdate" wire:loading.attr="disabled" @disabled(count($pending) === 0 || $isRunning || $isFailed || !$summary['production_gates_pass'])>Start system update</button>
                        </div>
                    </div>
                </section>

                <section class="system-update-card">
                    <div class="system-update-card-head"><h3>Recent update history</h3><p>Latest protected runs recorded on this server.</p></div>
                    <div class="system-update-card-body">
                        @if(count($history) > 0)
                            <div class="system-update-history">
                                @foreach(array_slice($history, 0, 6) as $item)
                                    <div class="system-update-history-item"><strong>{{ ucfirst($item['status'] ?? 'unknown') }}</strong><div class="system-update-code">{{ $item['id'] ?? '-' }}</div><div>{{ count($item['completed_migrations'] ?? []) }} migration(s) applied · {{ $item['completed_at'] ?? $item['started_at'] ?? '-' }}</div></div>
                                @endforeach
                            </div>
                        @else
                            <div class="system-update-empty" style="padding:10px;">No web-based update has been recorded yet.</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
