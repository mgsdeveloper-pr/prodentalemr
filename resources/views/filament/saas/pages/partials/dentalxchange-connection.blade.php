@php($details = $this->connectionDetails())
<div class="es-toolbar">
    <h2>DentalXChange</h2>
    <x-filament::badge color="gray">XConnect Eligibility</x-filament::badge>
    <x-filament::badge color="warning">Live patient submissions disabled</x-filament::badge>
</div>
<div class="es-toolbar" aria-label="Connection environment">
    @foreach (['sandbox' => 'Sandbox', 'production' => 'Production'] as $value => $label)
        <x-filament::button type="button" :color="$environment === $value ? 'primary' : 'gray'" wire:click="selectEnvironment('{{ $value }}')" wire:confirm="Switch environment? Unsaved credentials will be discarded." :aria-pressed="$environment === $value ? 'true' : 'false'">{{ $label }}</x-filament::button>
    @endforeach
</div>
<div class="es-grid">
    <section class="es-band">
        <h2>{{ ucfirst($environment) }} API key</h2>
        <p>Saved key: <strong>{{ $details['has_key'] ? 'Stored securely' : 'Not configured' }}</strong>. Leave the key blank to retain it.</p>
        @if ($environment === 'sandbox')<p>Sandbox access and an API key must be issued by DentalXChange.</p>@endif
        <form wire:submit="save">
            @if ($errors->any())<div role="alert" class="es-note">{{ $errors->first() }}</div>@endif
            {{ $this->form }}
            <div class="es-toolbar" style="margin-top:20px">
                <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled">Save settings</x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-signal" wire:click="testConnection" wire:confirm="Contact DentalXChange's health endpoint using the saved API key? No patient information is sent." wire:loading.attr="disabled">Test health connection</x-filament::button>
            </div>
        </form>
        @if ($testResult)<div class="es-note" role="status">{{ $testResult['message'] }}</div>@endif
    </section>
    <section class="es-band">
        <h2>Connection readiness</h2>
        @foreach ($details['checks'] as $check)
            <div class="es-check"><span>{{ $check['label'] }}</span><span class="{{ $check['ready'] ? 'es-ready' : 'es-pending' }}">{{ $check['ready'] ? 'Ready' : 'Pending' }}</span></div>
        @endforeach
        <p>Last test: {{ str_replace('_', ' ', $details['last_status']) }}</p>
        <div class="es-note">Health checks do not verify patient benefits. Account authentication, clinic/provider mapping and payer validation are pending. Enhanced Eligibility is not enabled.</div>
    </section>
</div>
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
