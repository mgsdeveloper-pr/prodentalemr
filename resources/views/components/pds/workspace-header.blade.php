@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'pds-workspace-header']) }}>
    <div class="pds-workspace-header__copy">
        @if ($eyebrow)
            <span class="pds-workspace-header__eyebrow">{{ $eyebrow }}</span>
        @endif

        <h1 class="pds-workspace-header__title">{{ $title }}</h1>

        @if ($description)
            <p class="pds-workspace-header__description">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="pds-workspace-header__actions">
            {{ $actions }}
        </div>
    @endisset
</header>
