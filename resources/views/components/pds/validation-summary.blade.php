@props(['errors' => null])

@php
    $messages = collect($errors?->all() ?? [])->filter();
@endphp

@if ($messages->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'pds-validation-summary rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800']) }} role="alert">
        <p class="font-semibold">Please review the highlighted fields.</p>
        <ul class="mt-2 list-disc space-y-1 ps-5">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
