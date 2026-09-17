@php($details = $this->connectionDetails())
<div class="es-toolbar"><h2>pVerify</h2><x-filament::badge color="warning">Live patient submissions disabled</x-filament::badge></div>
<div class="es-toolbar" aria-label="Connection environment">
    @foreach (['sandbox' => 'Test', 'production' => 'Production'] as $value => $label)
        <x-filament::button type="button" :color="$environment === $value ? 'primary' : 'gray'" wire:click="selectEnvironment('{{ $value }}')" wire:confirm="Switch environment? Unsaved credentials will be discarded." :aria-pressed="$environment === $value ? 'true' : 'false'">{{ $label }}</x-filament::button>
    @endforeach
</div>
<div class="es-grid">
    <section class="es-band">
        <h2>{{ $environment === 'sandbox' ? 'Test' : 'Production' }} authentication</h2>
        <p>Credentials: <strong>{{ $details['has_key'] ? 'Stored securely' : 'Not configured' }}</strong>. Leave both fields blank to retain them; replace both together.</p>
        <p>Use the Client API ID and Client Secret issued by pVerify for this environment.</p>
        <form wire:submit="save">
            @if ($errors->any())<div role="alert" class="es-note">{{ $errors->first() }}</div>@endif
            {{ $this->form }}
            <div class="es-toolbar" style="margin-top:20px">
                <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled">Save settings</x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-signal" wire:click="testConnection" wire:confirm="Contact pVerify's token endpoint using saved credentials? No patient information is sent." wire:loading.attr="disabled">Test authentication</x-filament::button>
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
        <div class="es-note">Authentication does not confirm dental benefits access. Payer/provider mapping, dental API entitlement and production validation remain pending.</div>
    </section>
</div>
<section class="es-band">
    <h2>Recent {{ $environment === 'sandbox' ? 'test' : 'production' }} activity</h2>
    <div class="es-scroll"><table><thead><tr><th>Time (UTC)</th><th>Event</th><th>Result</th></tr></thead><tbody>
        @forelse ($details['events'] as $event)
            <tr><td>{{ $event->created_at->utc()->format('M d, Y H:i') }}</td><td>{{ str_replace('_', ' ', $event->event) }}</td><td>{{ str_replace('_', ' ', $event->status) }}</td></tr>
        @empty
            <tr><td colspan="3">No connection activity yet.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
