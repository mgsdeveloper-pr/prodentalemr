<section {{ $attributes->merge(['class' => 'pds-sticky-action-bar']) }} aria-label="Sticky action bar">
    <x-pds.action-toolbar class="pds-sticky-action-bar__actions">
        {{ $slot }}
    </x-pds.action-toolbar>
</section>
