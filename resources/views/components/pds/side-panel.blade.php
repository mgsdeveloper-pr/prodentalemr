@props(['title' => null])

<aside {{ $attributes->merge(['class' => 'pds-side-panel rounded-lg border border-slate-200 bg-white p-4 shadow-sm']) }} aria-label="{{ $title ?? 'Side panel' }}">
    @if ($title)
        <h2 class="text-sm font-semibold text-slate-950">{{ $title }}</h2>
    @endif
    <div @class(['mt-3' => $title])>{{ $slot }}</div>
</aside>
