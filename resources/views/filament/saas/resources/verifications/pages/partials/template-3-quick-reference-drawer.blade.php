<aside
    class="vt3-reference-drawer"
    x-bind:class="{ 'is-open': utilityDrawerMode !== null }"
    x-bind:aria-hidden="(utilityDrawerMode === null).toString()"
    x-bind:aria-label="utilityDrawerMode === 'call' ? 'Insurance Call' : 'Quick Reference'"
>
    @if ($callingWorkspace['visible'] ?? false)
        @include('filament.saas.resources.verifications.pages.partials.telephony-call-control', [
            'callingWorkspace' => $callingWorkspace,
            'destinationNumber' => ($quickReference['phone'] ?? '-') !== '-' ? $quickReference['phone'] : '',
            'insuranceName' => $quickReference['insurance_name'] ?? 'Insurance',
            'edgeTrigger' => true,
        ])
    @endif

    <button
        type="button"
        class="vt3-reference-drawer__tab"
        x-bind:class="{ 'is-selected': utilityDrawerMode === 'reference' }"
        x-on:click="if (utilityDrawerMode === 'reference') { utilityDrawerMode = null } else { $dispatch('verification-close-telephony-drawer'); utilityDrawerMode = 'reference' }"
        x-bind:aria-expanded="(utilityDrawerMode === 'reference').toString()"
        aria-controls="template3-quick-reference-drawer-body"
        x-bind:title="utilityDrawerMode === 'reference' ? 'Close quick reference' : 'Open quick reference'"
    >
        <x-heroicon-o-chevron-left x-show="utilityDrawerMode !== 'reference'" aria-hidden="true" />
        <x-heroicon-o-chevron-right x-show="utilityDrawerMode === 'reference'" aria-hidden="true" />
        <span>Quick Reference</span>
    </button>

    <div x-cloak x-show="utilityDrawerMode === 'reference'" class="vt3-reference-drawer__header">
        <h2 id="template3-quick-reference-drawer-title">Quick Reference</h2>
        <button
            type="button"
            class="vt3-reference-drawer__close"
            x-on:click="$dispatch('verification-close-telephony-drawer'); utilityDrawerMode = null"
            aria-label="Close quick reference"
            title="Close quick reference"
        >
            <x-heroicon-o-x-mark aria-hidden="true" />
        </button>
    </div>

    <div x-cloak x-show="utilityDrawerMode === 'reference'" id="template3-quick-reference-drawer-body" class="vt3-reference-drawer__body">
        @foreach ($templateThreeQuickReferenceRows as $templateThreeQuickReferenceGroup => $templateThreeQuickReferenceFields)
            <section class="vt3-reference-drawer__group">
                <h3>{{ $templateThreeQuickReferenceGroup }}</h3>
                <dl>
                    @foreach ($templateThreeQuickReferenceFields as [$templateThreeQuickReferenceLabel, $templateThreeQuickReferenceValue])
                        <div class="vt3-reference-drawer__row">
                            <dt>{{ $templateThreeQuickReferenceLabel }}</dt>
                            <dd>
                                @if ($templateThreeQuickReferenceLabel === 'Insurance Phone' && filled($templateThreeQuickReferenceValue) && $templateThreeQuickReferenceValue !== '-' && ($callingWorkspace['available'] ?? false))
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('verification-open-telephony', { destination: @js(preg_replace('/[^0-9+]/', '', (string) $templateThreeQuickReferenceValue)), insuranceName: @js($quickReference['insurance_name'] ?? 'Insurance') })"
                                        aria-label="Call insurance at {{ $templateThreeQuickReferenceValue }}"
                                        title="Open portal dialer"
                                        style="appearance:none;padding:0;border:0;background:transparent;color:#0f766e;font:inherit;cursor:pointer;"
                                    >{{ $templateThreeQuickReferenceValue }}</button>
                                @else
                                    {{ filled($templateThreeQuickReferenceValue) ? $templateThreeQuickReferenceValue : '-' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach
    </div>
</aside>
