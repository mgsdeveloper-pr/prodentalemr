<x-filament-panels::page>
    <style>
        .eligibility-setup { color:#18212f; font-size:14px; line-height:1.5; letter-spacing:0; }
        .eligibility-setup h2 { font-size:18px; font-weight:700; margin:0; }
        .eligibility-setup p { margin:8px 0 16px; color:#536174; }
        .eligibility-setup .es-toolbar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:20px; }
        .eligibility-setup .es-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:32px; }
        .eligibility-setup .es-band { padding:22px 0; border-bottom:1px solid #dce3eb; }
        .eligibility-setup .es-check { display:flex; justify-content:space-between; gap:16px; padding:11px 0; border-bottom:1px solid #e8edf2; }
        .eligibility-setup .es-note { padding:12px 16px; border-left:3px solid #b7791f; background:#fffbeb; margin:12px 0; }
        .eligibility-setup .es-scroll { overflow-x:auto; }
        .eligibility-setup table { width:100%; border-collapse:collapse; background:#fff; }
        .eligibility-setup th,.eligibility-setup td { padding:11px 14px; border-bottom:1px solid #e3e9ef; text-align:left; overflow-wrap:anywhere; }
        .eligibility-setup th { font-weight:600; background:#f4f6f8; }
        .eligibility-setup .es-benefits td:nth-child(2) { white-space:nowrap; overflow-wrap:normal; }
        .eligibility-setup select { border:1px solid #cbd5e1; border-radius:6px; max-width:100%; padding:8px 32px 8px 12px; background-color:#fff; }
        .eligibility-setup .es-ready { color:#08756d; font-weight:600; }
        .eligibility-setup .es-pending { color:#885915; }
        @media(max-width:800px) { .eligibility-setup .es-grid { grid-template-columns:1fr; gap:16px; } }
    </style>
    <div class="eligibility-setup">
        <div class="es-toolbar">
            <x-filament::button tag="a" :href="\App\Filament\Saas\Pages\EligibilityConnections::getUrl()" color="gray" icon="heroicon-o-arrow-left">All providers</x-filament::button>
        </div>
        @if ($provider === 'stedi' && $this->storageReady())
            @include('filament.saas.pages.partials.stedi-connection')
        @elseif ($provider === 'stedi')
            <div role="status" class="es-note">Apply the eligibility connection database updates before configuring Stedi.</div>
        @elseif ($provider === 'pverify' && $this->storageReady())
            @include('filament.saas.pages.partials.pverify-connection')
        @elseif ($provider === 'pverify')
            <div role="status" class="es-note">Apply the eligibility connection database updates before configuring pVerify.</div>
        @elseif ($provider === 'dentalxchange' && $this->storageReady())
            @include('filament.saas.pages.partials.dentalxchange-connection')
        @elseif ($provider === 'dentalxchange')
            <div role="status" class="es-note">Apply the eligibility connection database updates before configuring DentalXChange.</div>
        @elseif ($provider === 'vyne' && $this->storageReady())
            @include('filament.saas.pages.partials.vyne-connection')
        @elseif ($provider === 'vyne')
            <div role="status" class="es-note">Apply the eligibility connection database updates before configuring Vyne.</div>
        @elseif ($provider !== 'zuub')
            <section class="es-band">
                <div class="es-toolbar"><h2>{{ \App\Services\Eligibility\EligibilityProviderCatalog::PROVIDERS[$provider] }}</h2><x-filament::badge color="gray">Not configured</x-filament::badge></div>
                <p>Eligibility processing is disabled.</p>
                <div class="es-note">Provider authentication, sandbox access, and response mapping are pending. No credentials or patient requests are collected for this provider yet.</div>
            </section>
        @elseif (! $this->storageReady())
            <div role="status" class="es-note">Database update required. Apply the eligibility connection migration before configuring Zuub.</div>
        @else
            @php($details = $this->connectionDetails())
            <div class="es-toolbar">
                <h2>Zuub</h2>
                <x-filament::badge color="warning">Eligibility submission not connected</x-filament::badge>
            </div>
            <div class="es-toolbar" aria-label="Connection environment">
                @foreach (['sandbox' => 'Sandbox', 'production' => 'Production'] as $value => $label)
                    <x-filament::button type="button" :color="$environment === $value ? 'primary' : 'gray'" wire:click="selectEnvironment('{{ $value }}')" wire:confirm="Switch environment? Unsaved credentials will be discarded." :aria-pressed="$environment === $value ? 'true' : 'false'">{{ $label }}</x-filament::button>
                @endforeach
            </div>
            <div class="es-grid">
                <section class="es-band" aria-labelledby="es-credentials">
                    <h2 id="es-credentials">{{ ucfirst($environment) }} credentials</h2>
                    <p>Saved credential: <strong>{{ $details['has_key'] ? 'Stored securely' : 'Not configured' }}</strong>. Leave the new credential blank to keep it.</p>
                    <form wire:submit="save">
                        @if ($errors->any())
                            <div role="alert" class="es-note">{{ $errors->first() }}</div>
                        @endif
                        {{ $this->form }}
                        <div class="es-toolbar" style="margin-top:20px">
                            <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled">Save settings</x-filament::button>
                            <x-filament::button type="button" color="gray" icon="heroicon-o-signal" wire:click="testConnection" wire:confirm="Test saved credentials against the documented non-patient endpoint? Unsaved changes are not used." wire:loading.attr="disabled">Test saved connection</x-filament::button>
                        </div>
                    </form>
                    @if ($testResult)
                        <div class="es-note" role="status">{{ $testResult['message'] }}</div>
                    @endif
                </section>
                <section class="es-band" aria-labelledby="es-readiness">
                    <h2 id="es-readiness">Connection readiness</h2>
                    @foreach ($details['checks'] as $check)
                        <div class="es-check"><span>{{ $check['label'] }}</span><span class="{{ $check['ready'] ? 'es-ready' : 'es-pending' }}">{{ $check['ready'] ? 'Ready' : 'Pending' }}</span></div>
                    @endforeach
                    <p>Last test: {{ $details['last_status'] }}</p>
                    <div class="es-note">Zuub partner documentation is required to finish authentication, payer mapping, and eligibility processing. Saving an API key does not enable patient requests.</div>
                </section>
            </div>
            <section class="es-band" aria-labelledby="es-example">
                <div class="es-toolbar"><h2 id="es-example">Local workflow test</h2><x-filament::badge color="gray">Fictional data only</x-filament::badge></div>
                <div class="es-toolbar">
                    <label for="es-scenario">Scenario</label>
                    <select id="es-scenario" wire:model="scenario">
                        @foreach (\App\Services\Eligibility\EligibilityDemonstration::SCENARIOS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-filament::button type="button" color="gray" icon="heroicon-o-play" wire:click="runDemonstration" wire:loading.attr="disabled">Run local example</x-filament::button>
                </div>
                @if ($demoResult)
                    <p><strong>{{ $demoResult['patient'] }}</strong> &middot; {{ $demoResult['status'] }}<br>{{ $demoResult['source'] }}</p>
                    @if ($demoResult['fields'])
                        <div class="es-scroll"><table class="es-benefits"><thead><tr><th>Benefit</th><th>Value</th><th>Review status</th></tr></thead><tbody>
                            @foreach ($demoResult['fields'] as $field)
                                <tr><td>{{ $field['label'] }}</td><td>{{ $field['value'] ?? 'Not returned' }}</td><td>{{ $field['state'] }}</td></tr>
                            @endforeach
                        </tbody></table></div>
                    @endif
                    <div role="status" class="es-note">{{ $demoResult['message'] }}</div>
                @endif
            </section>
            <section class="es-band" aria-labelledby="es-history">
                <h2 id="es-history">Recent {{ $environment }} activity</h2>
                <div class="es-scroll"><table><thead><tr><th>Time (UTC)</th><th>Event</th><th>Result</th></tr></thead><tbody>
                    @forelse ($details['events'] as $event)
                        <tr><td>{{ $event->created_at->utc()->format('M d, Y H:i') }}</td><td>{{ str($event->event)->replace('_', ' ')->ucfirst() }}</td><td>{{ str($event->status)->replace('_', ' ')->ucfirst() }}</td></tr>
                    @empty
                        <tr><td colspan="3">No connection activity yet.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
