@php
    $items = $items ?? [];
@endphp

@if (count($items) > 0)
    <div class="pd-appshell-status-bar" aria-label="Status">
        @foreach ($items as $item)
            <span class="pd-appshell-status-pill">{{ $item }}</span>
        @endforeach
    </div>
@endif
