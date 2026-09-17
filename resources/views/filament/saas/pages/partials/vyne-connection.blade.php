@php($details = $this->connectionDetails())
<div class="es-toolbar">
    <h2>Vyne / Onederful</h2>
    <x-filament::badge color="warning">Live patient submissions disabled</x-filament::badge>
</div>
<div class="es-toolbar" aria-label="Connection environment">
    @foreach (['sandbox' => 'Sandbox', 'production' => 'Production'] as $value => $label)
        <x-filament::button type="button" :color="$environment === $value ? 'primary' : 'gray'" wire:click="selectEnvironment('{{ $value }}')" wire:confirm="Switch environment? Unsaved credentials will be discarded." :aria-pressed="$environment === $value ? 'true' : 'false'">{{ $label }}</x-filament::button>
    @endforeach
</div>
<div class="es-grid">
    <section class="es-band">
        <h2>{{ $environment === 'sandbox' ? 'Sandbox API' : 'Production authentication' }}</h2>
        @if ($environment === 'sandbox')
            <p>No API key required. Only Vyne's fixed fictional test identity is sent; no clinic or patient records are used.</p>
        @else
            <p>Credentials: <strong>{{ $details['has_key'] ? 'Stored securely' : 'Not configured' }}</strong>. Leave both fields blank to retain them; replace both together.</p>
        @endif
        <form wire:submit="save">
            @if ($errors->any())<div role="alert" class="es-note">{{ $errors->first() }}</div>@endif
            {{ $this->form }}
            <div class="es-toolbar" style="margin-top:20px">
                <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled">Save settings</x-filament::button>
            </div>
        </form>
        <div class="es-toolbar">
            @if ($environment === 'sandbox')
                <label for="vyne-payer">Test payer</label>
                <select id="vyne-payer" wire:model="sandboxPayer">
                    @foreach (\App\Services\Eligibility\VyneConnectionService::SANDBOX_PAYERS as $payerId)
                        <option value="{{ $payerId }}">{{ str_replace('_', ' ', $payerId) }}</option>
                    @endforeach
                </select>
            @endif
            <x-filament::button type="button" color="gray" icon="heroicon-o-signal" wire:click="testConnection" wire:confirm="Contact Vyne using saved settings? Sandbox sends only a fixed fictional identity; production tests authentication only." wire:loading.attr="disabled">{{ $environment === 'sandbox' ? 'Test sandbox API' : 'Test authentication' }}</x-filament::button>
        </div>
        @if ($testResult)<div class="es-note" role="status">{{ $testResult['message'] }}</div>@endif
    </section>
    <section class="es-band">
        <h2>Connection readiness</h2>
        @foreach ($details['checks'] as $check)
            <div class="es-check"><span>{{ $check['label'] }}</span><span class="{{ $check['ready'] ? 'es-ready' : 'es-pending' }}">{{ $check['ready'] ? 'Ready' : 'Pending' }}</span></div>
        @endforeach
        <p>Last test: {{ str_replace('_', ' ', $details['last_status']) }}</p>
        <div class="es-note">Sandbox results are static examples. Production eligibility requires clinic/payer mapping and validation before activation.</div>
    </section>
</div>
@if (isset($testResult['benefits']))
    <section class="es-band">
        <div class="es-toolbar"><h2>Sandbox benefit response</h2><x-filament::badge color="warning">Test data only</x-filament::badge></div>
        <p>Coverage: {{ $testResult['coverage'] }}. Values are shown as returned, not applied to verification forms.</p>
        <div class="es-scroll"><table style="min-width:760px"><thead><tr><th>Benefit</th><th>Category</th><th>Network</th><th>Period</th><th>Level</th><th>Value</th></tr></thead><tbody>
            @forelse ($testResult['benefits'] as $benefit)
                <tr>@foreach (['label', 'category', 'network', 'period', 'level', 'value'] as $column)<td>{{ $benefit[$column] }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="6">No deductible, maximum or percentage values returned.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
@endif
<section class="es-band">
    <h2>Recent {{ $environment }} activity</h2>
    <div class="es-scroll"><table><thead><tr><th>Time (UTC)</th><th>Event</th><th>Result</th></tr></thead><tbody>
        @forelse ($details['events'] as $event)
            <tr><td>{{ $event->created_at->utc()->format('M d, Y H:i') }}</td><td>{{ str_replace('_', ' ', $event->event) }}</td><td>{{ str_replace('_', ' ', $event->status) }}</td></tr>
        @empty
            <tr><td colspan="3">No connection activity yet.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
