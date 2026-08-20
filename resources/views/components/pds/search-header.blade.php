@props(['placeholder' => 'Search', 'name' => 'search'])

<label {{ $attributes->merge(['class' => 'pds-search-header flex min-h-9 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-600']) }}>
    <span class="sr-only">{{ $placeholder }}</span>
    <input name="{{ $name }}" placeholder="{{ $placeholder }}" class="w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0">
</label>
