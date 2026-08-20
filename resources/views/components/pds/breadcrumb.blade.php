@props(['items' => []])

<nav {{ $attributes->merge(['class' => 'pds-breadcrumb text-sm text-slate-600']) }} aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1">
        @foreach ($items as $item)
            <li class="flex items-center gap-1">
                @if (! $loop->first)
                    <span class="text-slate-400" aria-hidden="true">/</span>
                @endif

                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="font-medium text-slate-600 hover:text-teal-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">{{ $item['label'] }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
