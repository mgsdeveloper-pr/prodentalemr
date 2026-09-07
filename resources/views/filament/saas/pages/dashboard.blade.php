<x-filament-panels::page>
    @php($dashboard = $this->dashboardData())
    @php($revenueMaximum = max(collect($dashboard['revenue'])->max(fn (array $month): float => max($month['invoiced'], $month['collected'])) ?: 0, 1))

    <div class="saas-dashboard">
        <header class="saas-dashboard__header">
            <div class="saas-dashboard__heading">
                <span>Platform overview</span>
                <h1>SaaS Dashboard</h1>
                <p>Monitor revenue, client health, and work requiring administrative follow-up.</p>
            </div>
            <div class="saas-dashboard__header-actions">
                <label class="saas-dashboard__period">
                    <span>Reporting period</span>
                    <select wire:model.live="period" aria-label="Reporting period">
                        <option value="month">This month</option>
                        <option value="quarter">This quarter</option>
                        <option value="year">This year</option>
                    </select>
                </label>
                <a class="saas-dashboard__button is-secondary" href="{{ $dashboard['clients_url'] }}" wire:navigate>
                    <x-heroicon-o-building-office-2 />
                    <span>View Clients</span>
                </a>
                <a class="saas-dashboard__button is-primary" href="{{ $dashboard['onboarding_url'] }}" wire:navigate>
                    <x-heroicon-o-plus />
                    <span>Add Client</span>
                </a>
            </div>
            <div class="saas-dashboard__freshness">
                <span>{{ $dashboard['period_label'] }}</span>
                <i aria-hidden="true"></i>
                <span>Updated {{ $dashboard['refreshed_at'] }}</span>
            </div>
        </header>

        <section class="saas-dashboard__kpis" aria-label="Business overview">
            @foreach ($dashboard['kpis'] as $kpi)
                <a class="saas-dashboard__kpi is-{{ $kpi['tone'] }}" href="{{ $kpi['url'] }}" wire:navigate>
                    <span>{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                    <small>{{ $kpi['detail'] }}</small>
                    <x-heroicon-o-arrow-up-right />
                </a>
            @endforeach
        </section>

        <section class="saas-dashboard__section saas-dashboard__attention" aria-labelledby="attention-title">
            <div class="saas-dashboard__section-heading">
                <div>
                    <h2 id="attention-title">Attention Required</h2>
                    <p>Items that need administrative review or follow-up.</p>
                </div>
                @if ($dashboard['attention'] !== [])
                    <span class="saas-dashboard__count">{{ collect($dashboard['attention'])->sum('count') }} open</span>
                @endif
            </div>

            @if ($dashboard['attention'] === [])
                <div class="saas-dashboard__healthy">
                    <span><x-heroicon-o-check-circle /></span>
                    <div><strong>No immediate action required</strong><p>Client onboarding, subscriptions, trials, and billing have no current exceptions.</p></div>
                </div>
            @else
                <div class="saas-dashboard__attention-grid">
                    @foreach ($dashboard['attention'] as $item)
                        <a href="{{ $item['url'] }}" wire:navigate class="saas-dashboard__attention-item is-{{ $item['tone'] }}">
                            <span class="saas-dashboard__attention-count">{{ $item['count'] }}</span>
                            <span><strong>{{ $item['label'] }}</strong><small>{{ $item['description'] }}</small></span>
                            <x-heroicon-o-chevron-right />
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="saas-dashboard__operations">
            <section class="saas-dashboard__section saas-dashboard__revenue" aria-labelledby="revenue-title">
                <div class="saas-dashboard__section-heading">
                    <div><h2 id="revenue-title">Revenue Trend</h2><p>Invoices issued compared with payments collected.</p></div>
                    <div class="saas-dashboard__legend"><span class="is-invoiced">Invoiced</span><span class="is-collected">Collected</span></div>
                </div>
                <div class="saas-dashboard__chart" role="img" aria-label="Six-month invoiced and collected revenue chart">
                    @foreach ($dashboard['revenue'] as $month)
                        <div class="saas-dashboard__chart-month">
                            <div class="saas-dashboard__bars">
                                <span class="is-invoiced" style="height: {{ max(($month['invoiced'] / $revenueMaximum) * 100, $month['invoiced'] > 0 ? 5 : 1) }}%" title="{{ $month['label'] }} invoiced: ${{ number_format($month['invoiced'], 2) }}"></span>
                                <span class="is-collected" style="height: {{ max(($month['collected'] / $revenueMaximum) * 100, $month['collected'] > 0 ? 5 : 1) }}%" title="{{ $month['label'] }} collected: ${{ number_format($month['collected'], 2) }}"></span>
                            </div>
                            <strong>{{ $month['label'] }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="saas-dashboard__chart-totals">
                    <span><small>Six-month invoiced</small><strong>${{ number_format(collect($dashboard['revenue'])->sum('invoiced'), 2) }}</strong></span>
                    <span><small>Six-month collected</small><strong>${{ number_format(collect($dashboard['revenue'])->sum('collected'), 2) }}</strong></span>
                </div>
            </section>

            <section class="saas-dashboard__section saas-dashboard__follow-up" aria-labelledby="follow-up-title">
                <div class="saas-dashboard__section-heading">
                    <div><h2 id="follow-up-title">Accounts Requiring Follow-Up</h2><p>Highest-priority client exceptions and their next action.</p></div>
                    <a href="{{ $dashboard['clients_url'] }}" wire:navigate>View all clients <x-heroicon-o-arrow-right /></a>
                </div>
                @if ($dashboard['accounts'] === [])
                    <div class="saas-dashboard__table-empty"><x-heroicon-o-check-circle /><strong>No accounts require follow-up</strong><span>Current client accounts have no detected setup, service, retention, or billing exceptions.</span></div>
                @else
                    <div class="saas-dashboard__table-wrap">
                        <table>
                            <thead><tr><th>Client</th><th>Plan</th><th>Status</th><th>Financial Risk</th><th>Setup</th><th>Owner</th><th>Next Action</th></tr></thead>
                            <tbody>
                                @foreach ($dashboard['accounts'] as $account)
                                    <tr>
                                        <td><strong>{{ $account['client'] }}</strong></td>
                                        <td>{{ $account['plan'] }}</td>
                                        <td><span class="saas-dashboard__status is-{{ $account['tone'] }}">{{ $account['status'] }}</span></td>
                                        <td>{{ $account['risk'] }}</td>
                                        <td>{{ $account['setup'] }}</td>
                                        <td>{{ $account['owner'] }}</td>
                                        <td><a href="{{ $account['url'] }}" wire:navigate>{{ $account['next_action'] }} <x-heroicon-o-arrow-right /></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <style>
        .saas-dashboard{--sd-border:#dbe4ee;--sd-navy:#0f172a;--sd-muted:#64748b;display:flex;flex-direction:column;gap:18px}.saas-dashboard__header{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px 24px;padding:2px 0 18px;border-bottom:1px solid var(--sd-border)}.saas-dashboard__heading>span{color:#0f766e;font-size:11px;font-weight:800;text-transform:uppercase}.saas-dashboard__heading h1{margin:5px 0 0;color:var(--sd-navy);font-size:28px;font-weight:850;line-height:1.2}.saas-dashboard__heading p{margin:6px 0 0;color:var(--sd-muted);font-size:13px}.saas-dashboard__header-actions{display:flex;align-items:flex-end;gap:8px}.saas-dashboard__period{display:grid;gap:4px}.saas-dashboard__period>span{color:var(--sd-muted);font-size:10px;font-weight:800}.saas-dashboard__period select{height:40px;min-width:145px;padding:0 34px 0 11px;border:1px solid #cfd9e6;border-radius:6px;background:#fff;color:#334155;font-size:12px;font-weight:700}.saas-dashboard__button{display:inline-flex;align-items:center;justify-content:center;gap:7px;height:40px;padding:0 13px;border:1px solid #cfd9e6;border-radius:6px;background:#fff;color:#334155;font-size:12px;font-weight:800;text-decoration:none;white-space:nowrap}.saas-dashboard__button svg{width:16px;height:16px}.saas-dashboard__button.is-primary{border-color:#0f766e;background:#0f766e;color:#fff}.saas-dashboard__button:hover{border-color:#99d5d1;color:#0f766e}.saas-dashboard__button.is-primary:hover{border-color:#115e59;background:#115e59;color:#fff}.saas-dashboard__freshness{grid-column:1/-1;display:flex;align-items:center;gap:9px;color:#667085;font-size:11px}.saas-dashboard__freshness i{width:3px;height:3px;border-radius:999px;background:#94a3b8}.saas-dashboard__kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));border:1px solid var(--sd-border);border-radius:8px;background:#fff;overflow:hidden}.saas-dashboard__kpi{position:relative;display:flex;min-width:0;min-height:126px;flex-direction:column;padding:17px;border-right:1px solid #edf2f7;color:inherit;text-decoration:none}.saas-dashboard__kpi:last-child{border-right:0}.saas-dashboard__kpi>span{color:var(--sd-muted);font-size:11px;font-weight:700}.saas-dashboard__kpi>strong{margin-top:8px;color:var(--sd-navy);font-size:23px;font-weight:850;line-height:1.15}.saas-dashboard__kpi>small{margin-top:auto;padding-top:9px;color:#667085;font-size:10px;line-height:1.35}.saas-dashboard__kpi>svg{position:absolute;right:14px;top:15px;width:15px;height:15px;color:#98a2b3}.saas-dashboard__kpi:hover{background:#fbfefe}.saas-dashboard__kpi.is-warning{box-shadow:inset 0 3px 0 #f59e0b}.saas-dashboard__kpi.is-danger{box-shadow:inset 0 3px 0 #dc2626}.saas-dashboard__kpi.is-primary{box-shadow:inset 0 3px 0 #0f766e}.saas-dashboard__section{border:1px solid var(--sd-border);border-radius:8px;background:#fff;overflow:hidden}.saas-dashboard__section-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px 17px;border-bottom:1px solid #edf2f7}.saas-dashboard__section-heading h2{margin:0;color:var(--sd-navy);font-size:15px;font-weight:850}.saas-dashboard__section-heading p{margin:3px 0 0;color:var(--sd-muted);font-size:11px}.saas-dashboard__section-heading>a{display:inline-flex;align-items:center;gap:5px;color:#0f766e;font-size:11px;font-weight:800;text-decoration:none}.saas-dashboard__section-heading>a svg{width:14px;height:14px}.saas-dashboard__count{padding:4px 8px;border:1px solid #fed7aa;border-radius:999px;background:#fff7ed;color:#b45309;font-size:10px;font-weight:800}.saas-dashboard__attention-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}.saas-dashboard__attention-item{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:11px;min-height:72px;padding:13px 16px;border-right:1px solid #edf2f7;border-bottom:1px solid #edf2f7;color:inherit;text-decoration:none}.saas-dashboard__attention-item:nth-child(3n){border-right:0}.saas-dashboard__attention-count{display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:999px;background:#f8fafc;color:#475569;font-size:13px;font-weight:850}.saas-dashboard__attention-item strong,.saas-dashboard__attention-item small{display:block}.saas-dashboard__attention-item strong{color:var(--sd-navy);font-size:12px}.saas-dashboard__attention-item small{margin-top:3px;color:var(--sd-muted);font-size:10px}.saas-dashboard__attention-item>svg{width:15px;height:15px;color:#98a2b3}.saas-dashboard__attention-item.is-warning .saas-dashboard__attention-count{background:#fff7ed;color:#b45309}.saas-dashboard__attention-item.is-danger .saas-dashboard__attention-count{background:#fef2f2;color:#b42318}.saas-dashboard__attention-item.is-info .saas-dashboard__attention-count{background:#eff6ff;color:#1d4ed8}.saas-dashboard__attention-item:hover{background:#fbfefe}.saas-dashboard__healthy{display:flex;align-items:center;gap:12px;padding:18px}.saas-dashboard__healthy>span{display:inline-flex;width:38px;height:38px;align-items:center;justify-content:center;border-radius:999px;background:#effaf5;color:#067647}.saas-dashboard__healthy svg{width:20px;height:20px}.saas-dashboard__healthy strong{display:block;color:var(--sd-navy);font-size:12px}.saas-dashboard__healthy p{margin:3px 0 0;color:var(--sd-muted);font-size:11px}.saas-dashboard__operations{display:grid;grid-template-columns:minmax(320px,.8fr) minmax(0,1.65fr);gap:18px;align-items:start}.saas-dashboard__legend{display:flex;gap:13px;color:var(--sd-muted);font-size:10px;font-weight:700}.saas-dashboard__legend span{display:inline-flex;align-items:center;gap:5px}.saas-dashboard__legend span:before{content:'';width:7px;height:7px;border-radius:2px;background:#94a3b8}.saas-dashboard__legend .is-invoiced:before{background:#cbd5e1}.saas-dashboard__legend .is-collected:before{background:#0f766e}.saas-dashboard__chart{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;height:210px;padding:22px 18px 12px;border-bottom:1px solid #edf2f7}.saas-dashboard__chart-month{display:grid;grid-template-rows:minmax(0,1fr) auto;gap:8px;min-width:0;text-align:center}.saas-dashboard__bars{display:flex;height:100%;align-items:flex-end;justify-content:center;gap:4px;border-bottom:1px solid #e2e8f0}.saas-dashboard__bars span{display:block;width:min(18px,38%);min-height:2px;border-radius:3px 3px 0 0}.saas-dashboard__bars .is-invoiced{background:#cbd5e1}.saas-dashboard__bars .is-collected{background:#0f766e}.saas-dashboard__chart-month>strong{color:#667085;font-size:10px}.saas-dashboard__chart-totals{display:grid;grid-template-columns:repeat(2,1fr)}.saas-dashboard__chart-totals>span{padding:13px 17px}.saas-dashboard__chart-totals>span+span{border-left:1px solid #edf2f7}.saas-dashboard__chart-totals small,.saas-dashboard__chart-totals strong{display:block}.saas-dashboard__chart-totals small{color:var(--sd-muted);font-size:10px}.saas-dashboard__chart-totals strong{margin-top:4px;color:var(--sd-navy);font-size:14px}.saas-dashboard__table-wrap{overflow-x:auto}.saas-dashboard__follow-up table{width:100%;min-width:880px;border-collapse:collapse}.saas-dashboard__follow-up th{padding:10px 12px;border-bottom:1px solid var(--sd-border);background:#f8fafc;color:#475569;font-size:10px;font-weight:800;text-align:left;white-space:nowrap}.saas-dashboard__follow-up td{padding:12px;border-bottom:1px solid #edf2f7;color:#475569;font-size:11px;vertical-align:middle}.saas-dashboard__follow-up tbody tr:last-child td{border-bottom:0}.saas-dashboard__follow-up td>strong{color:var(--sd-navy);font-size:11px}.saas-dashboard__follow-up td>a{display:inline-flex;align-items:center;gap:4px;color:#0f766e;font-weight:800;text-decoration:none;white-space:nowrap}.saas-dashboard__follow-up td>a svg{width:13px;height:13px}.saas-dashboard__status{display:inline-flex;padding:4px 7px;border:1px solid #dbe4ee;border-radius:999px;background:#f8fafc;color:#475569;font-size:9px;font-weight:800;white-space:nowrap}.saas-dashboard__status.is-warning{border-color:#fed7aa;background:#fff7ed;color:#b45309}.saas-dashboard__status.is-danger{border-color:#fecaca;background:#fef2f2;color:#b42318}.saas-dashboard__status.is-info{border-color:#bfdbfe;background:#eff6ff;color:#1d4ed8}.saas-dashboard__table-empty{display:flex;min-height:250px;align-items:center;justify-content:center;flex-direction:column;gap:7px;padding:30px;color:var(--sd-muted);text-align:center}.saas-dashboard__table-empty svg{width:28px;height:28px;color:#0f766e}.saas-dashboard__table-empty strong{color:var(--sd-navy);font-size:13px}.saas-dashboard__table-empty span{max-width:410px;font-size:11px;line-height:1.5}@media(max-width:1280px){.saas-dashboard__kpis{grid-template-columns:repeat(3,1fr)}.saas-dashboard__kpi:nth-child(3){border-right:0}.saas-dashboard__kpi:nth-child(-n+3){border-bottom:1px solid #edf2f7}.saas-dashboard__operations{grid-template-columns:1fr}.saas-dashboard__attention-grid{grid-template-columns:repeat(2,1fr)}.saas-dashboard__attention-item:nth-child(3n){border-right:1px solid #edf2f7}.saas-dashboard__attention-item:nth-child(2n){border-right:0}}@media(max-width:800px){.saas-dashboard__header{grid-template-columns:1fr}.saas-dashboard__header-actions{align-items:stretch;flex-wrap:wrap}.saas-dashboard__period{flex:1 1 160px}.saas-dashboard__period select{width:100%}.saas-dashboard__button{flex:1 1 auto}.saas-dashboard__kpis{grid-template-columns:1fr}.saas-dashboard__kpi{min-height:112px;border-right:0;border-bottom:1px solid #edf2f7}.saas-dashboard__kpi:last-child{border-bottom:0}.saas-dashboard__attention-grid{grid-template-columns:1fr}.saas-dashboard__attention-item,.saas-dashboard__attention-item:nth-child(3n){border-right:0}.saas-dashboard__section-heading{align-items:flex-start;flex-direction:column}.saas-dashboard__legend{align-self:flex-start}}
    </style>
</x-filament-panels::page>
