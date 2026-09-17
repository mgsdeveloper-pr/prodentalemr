<x-filament-panels::page>
    <style>
        .calling-providers { background:#fff; border:1px solid #dce3eb; border-radius:8px; font-size:14px; }
        .calling-provider-row { display:grid; grid-template-columns:minmax(150px,1fr) minmax(200px,2fr) auto; align-items:center; gap:24px; padding:24px; }
        .calling-provider-row + .calling-provider-row { border-top:1px solid #dce3eb; }
        .calling-provider-row h2 { font-size:16px; font-weight:600; margin:0; }
        .calling-provider-row p { margin:6px 0 0; color:#536174; }
        .calling-provider-toggle { display:flex; align-items:center; gap:10px; }
        .calling-provider-toggle input { appearance:none; width:40px; height:24px; border:0; border-radius:24px; background:#94a3b8; position:relative; cursor:pointer; }
        .calling-provider-toggle input::after { content:''; position:absolute; width:18px; height:18px; left:3px; top:3px; border-radius:50%; background:#fff; }
        .calling-provider-toggle input:checked { background:#087f77; }
        .calling-provider-toggle input:checked::after { left:19px; }
        .calling-provider-toggle input:focus-visible { outline:2px solid #087f77; outline-offset:3px; }
        .calling-provider-toggle input:disabled { opacity:.5; cursor:not-allowed; }
        @media(max-width:640px) { .calling-provider-row { grid-template-columns:1fr; gap:16px; padding:20px; } }
    </style>
    @if (! $this->storageReady())<p role="status">Database update required before changing the Twilio option.</p>@endif
    <div class="calling-providers">
        <section class="calling-provider-row">
            <h2>MightyCall</h2>
            <p>Existing accounts and agent assignments remain unchanged.</p>
            <x-filament::button tag="a" :href="\App\Filament\Saas\Resources\TelephonyAccounts\TelephonyAccountResource::getUrl()" color="gray" icon="heroicon-o-cog-6-tooth">Manage accounts</x-filament::button>
        </section>
        <section class="calling-provider-row">
            <h2>Twilio</h2>
            <div><x-filament::badge color="warning">Setup pending</x-filament::badge><p>Provider preference only. Twilio calling is not connected; existing calls are unaffected.</p></div>
            <label class="calling-provider-toggle">
                <input type="checkbox" role="switch" aria-label="Enable Twilio option" @checked($this->twilioEnabled()) @disabled(! $this->storageReady() || ! auth()->user()->canPerformSaasModuleAction('calling', 'update')) wire:change="setTwilioEnabled($event.target.checked)" wire:loading.attr="disabled">
                <span>{{ $this->twilioEnabled() ? 'Enabled' : 'Disabled' }}</span>
            </label>
        </section>
    </div>
</x-filament-panels::page>
