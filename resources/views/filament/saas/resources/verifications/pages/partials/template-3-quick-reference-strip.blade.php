<section class="uel2-quick-strip" aria-labelledby="template3-quick-reference-title" x-data="{ quickReferenceOpen: true }">
    <div class="uel2-quick-strip__header">
        <h3 id="template3-quick-reference-title">Quick Reference</h3>
        <button
            type="button"
            class="uel2-quick-strip__toggle"
            x-on:click="quickReferenceOpen = ! quickReferenceOpen"
            x-bind:aria-expanded="quickReferenceOpen.toString()"
            aria-controls="template3-quick-reference-body"
            x-bind:title="quickReferenceOpen ? 'Collapse quick reference' : 'Expand quick reference'"
        >
            <span x-show="quickReferenceOpen" aria-hidden="true"><x-heroicon-o-chevron-up /></span>
            <span x-show="! quickReferenceOpen" x-cloak aria-hidden="true"><x-heroicon-o-chevron-down /></span>
            <span class="uel2-sr-only" x-text="quickReferenceOpen ? 'Collapse quick reference' : 'Expand quick reference'"></span>
        </button>
    </div>
    <div id="template3-quick-reference-body" class="uel2-quick-strip__body" x-show="quickReferenceOpen">
        @foreach ($templateThreeQuickReferenceRows as $templateThreeQuickReferenceGroup => $templateThreeQuickReferenceFields)
            <div class="uel2-quick-strip__row">
                <div class="uel2-quick-strip__row-title">
                    <span>{{ $templateThreeQuickReferenceGroup }}</span>
                </div>
                <div class="uel2-quick-strip__fields {{ $loop->first ? 'uel2-quick-strip__fields--patient' : 'uel2-quick-strip__fields--context' }}">
                    @foreach ($templateThreeQuickReferenceFields as [$templateThreeQuickReferenceLabel, $templateThreeQuickReferenceValue])
                        <div class="uel2-quick-strip__field">
                            <div class="uel2-quick-strip__label">{{ $templateThreeQuickReferenceLabel }}</div>
                            <div class="uel2-quick-strip__value">
                                @if ($templateThreeQuickReferenceLabel === 'Insurance Phone' && filled($templateThreeQuickReferenceValue) && $templateThreeQuickReferenceValue !== '-' && ($callingWorkspace['available'] ?? false))
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('verification-open-telephony', { destination: @js(preg_replace('/[^0-9+]/', '', (string) $templateThreeQuickReferenceValue)), insuranceName: @js($quickReference['insurance_name'] ?? 'Insurance') })"
                                        aria-label="Call insurance at {{ $templateThreeQuickReferenceValue }}"
                                        title="Open portal dialer"
                                        style="appearance:none;padding:0;border:0;background:transparent;color:inherit;font:inherit;cursor:pointer;"
                                    >{{ $templateThreeQuickReferenceValue }}</button>
                                @else
                                    {{ filled($templateThreeQuickReferenceValue) ? $templateThreeQuickReferenceValue : '-' }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
