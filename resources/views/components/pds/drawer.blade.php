@props(['title' => null, 'open' => false, 'side' => 'right'])

@php
    $sideClass = $side === 'left' ? 'left-0' : 'right-0';
@endphp

<aside @class(["pds-drawer fixed inset-y-0 {$sideClass} z-50 w-full max-w-md border-slate-200 bg-white p-5 shadow-xl" => true, 'hidden' => ! $open]) aria-label="{{ $title ?? 'Drawer' }}">
    @if ($title)
        <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
    @endif
    <div @class(['mt-4' => $title])>{{ $slot }}</div>
</aside>
