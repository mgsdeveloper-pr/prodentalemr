@props(['title' => 'Confirm action', 'open' => false])

<div @class(['pds-confirmation-dialog fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4' => true, 'hidden' => ! $open]) role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
        <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
        <div class="mt-3 text-sm text-slate-600">{{ $slot }}</div>
        @isset($actions)
            <div class="mt-5 flex justify-end gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
