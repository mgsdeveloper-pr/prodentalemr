<x-filament-panels::page>
    <style>
        .ep-list { background:#fff; border:1px solid #dce3eb; border-radius:8px; overflow-x:auto; }
        .ep-list table { width:100%; min-width:680px; border-collapse:collapse; font-size:14px; letter-spacing:0; }
        .ep-list th,.ep-list td { text-align:left; padding:18px 22px; border-bottom:1px solid #e5eaf0; }
        .ep-list th { background:#f6f8fa; color:#526174; font-size:12px; font-weight:600; }
        .ep-list tbody tr:last-child td { border-bottom:0; }
        .ep-list .ep-provider { font-weight:600; min-width:160px; }
        .ep-switch { display:inline-flex; gap:10px; align-items:center; white-space:nowrap; }
        .ep-switch input { appearance:none; width:36px; height:20px; border-radius:20px; background:#cbd5e1; border:0; position:relative; cursor:pointer; flex-shrink:0; }
        .ep-switch input::after { content:''; position:absolute; width:16px; height:16px; top:2px; left:2px; background:#fff; border-radius:50%; }
        .ep-switch input:checked { background:#087f77; }
        .ep-switch input:checked::after { left:18px; }
        .ep-switch input:disabled { cursor:not-allowed; opacity:.65; }
        .ep-switch input:focus-visible { outline:2px solid #087f77; outline-offset:3px; }
        .ep-message { padding:12px 16px; background:#fffbeb; border-left:3px solid #b7791f; font-size:14px; }
        @media(max-width:640px) { .ep-list th,.ep-list td { padding:14px 12px; } }
    </style>
    @if (! app(\App\Services\Eligibility\EligibilityProviderCatalog::class)->storageReady())
        <div class="ep-message" role="status">Database update required before changing provider activation.</div>
    @endif
    @error('provider_activation')<div class="ep-message" role="alert">{{ $message }}</div>@enderror
    <div class="ep-list">
        <table>
            <thead><tr><th scope="col">Provider</th><th scope="col">Enabled</th><th scope="col">Setup status</th><th scope="col">Settings</th></tr></thead>
            <tbody>
                @foreach ($this->providerRows() as $row)
                    <tr wire:key="provider-{{ $row['provider'] }}">
                        <td class="ep-provider">{{ $row['name'] }}</td>
                        <td>
                            <label class="ep-switch" title="{{ $row['ready'] || $row['enabled'] ? 'Allow new eligibility requests' : 'Complete provider setup before enabling' }}">
                                <input type="checkbox" role="switch" aria-label="Enable {{ $row['name'] }}" @checked($row['enabled']) @disabled(! $row['ready'] && ! $row['enabled']) wire:change="setProviderEnabled('{{ $row['provider'] }}', $event.target.checked)" wire:loading.attr="disabled">
                                <span>{{ $row['enabled'] ? 'On' : 'Off' }}</span>
                            </label>
                        </td>
                        <td><x-filament::badge :color="$row['provider'] === 'zuub' ? 'warning' : 'gray'">{{ $row['status'] }}</x-filament::badge></td>
                        <td><x-filament::button tag="a" :href="\App\Filament\Saas\Pages\EligibilityConnectionSettings::getUrl(['provider' => $row['provider']])" color="gray" icon="heroicon-o-cog-6-tooth" :aria-label="'Configure '.$row['name']">Configure</x-filament::button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
