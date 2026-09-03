<aside
    class="vt3-reference-drawer"
    x-bind:class="{ 'is-open': quickReferenceDrawerOpen }"
    x-bind:aria-hidden="(! quickReferenceDrawerOpen).toString()"
    aria-labelledby="template3-quick-reference-drawer-title"
>
    <button
        type="button"
        class="vt3-reference-drawer__tab"
        x-on:click="quickReferenceDrawerOpen = ! quickReferenceDrawerOpen"
        x-bind:aria-expanded="quickReferenceDrawerOpen.toString()"
        aria-controls="template3-quick-reference-drawer-body"
        x-bind:title="quickReferenceDrawerOpen ? 'Close quick reference' : 'Open quick reference'"
    >
        <x-heroicon-o-chevron-left x-show="! quickReferenceDrawerOpen" aria-hidden="true" />
        <x-heroicon-o-chevron-right x-show="quickReferenceDrawerOpen" aria-hidden="true" />
        <span>Quick Reference</span>
    </button>

    <div class="vt3-reference-drawer__header">
        <h2 id="template3-quick-reference-drawer-title">Quick Reference</h2>
        <button
            type="button"
            class="vt3-reference-drawer__close"
            x-on:click="quickReferenceDrawerOpen = false"
            aria-label="Close quick reference"
            title="Close quick reference"
        >
            <x-heroicon-o-x-mark aria-hidden="true" />
        </button>
    </div>

    <div id="template3-quick-reference-drawer-body" class="vt3-reference-drawer__body">
        @foreach ($templateThreeQuickReferenceRows as $templateThreeQuickReferenceGroup => $templateThreeQuickReferenceFields)
            <section class="vt3-reference-drawer__group">
                <h3>{{ $templateThreeQuickReferenceGroup }}</h3>
                <dl>
                    @foreach ($templateThreeQuickReferenceFields as [$templateThreeQuickReferenceLabel, $templateThreeQuickReferenceValue])
                        <div class="vt3-reference-drawer__row">
                            <dt>{{ $templateThreeQuickReferenceLabel }}</dt>
                            <dd>
                                @if ($templateThreeQuickReferenceLabel === 'Insurance Phone' && filled($templateThreeQuickReferenceValue) && $templateThreeQuickReferenceValue !== '-')
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $templateThreeQuickReferenceValue) }}">{{ $templateThreeQuickReferenceValue }}</a>
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
