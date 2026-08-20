@props(['title' => null, 'open' => false])

<div @class(['pds-modal fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4' => true, 'hidden' => ! $open]) role="dialog" aria-modal="true" aria-label="{{ $title ?? 'Modal' }}">
    <div {{ $attributes->merge(['class' => 'w-full max-w-lg rounded-lg bg-white p-5 shadow-xl']) }}>
        @if ($title)
            <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
        @endif
        <div @class(['mt-4' => $title])>{{ $slot }}</div>
    </div>
</div>
