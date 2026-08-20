@php
    $user = auth()->user();
    $organizationName = $user?->organization?->name;
    $environment = app()->environment();
    $environmentLabel = match ($environment) {
        'production' => 'Production',
        'uat', 'staging' => 'UAT',
        'local', 'development' => 'Development',
        default => ucfirst((string) $environment),
    };
    $version = config('app.version') ?: '1.0';
    $build = config('app.build') ?: 'local';
@endphp

<footer class="pd-appshell-footer" aria-label="Application status">
    <span>ProDental v{{ $version }}</span>
    <span>{{ ucfirst((string) $workspace) }}</span>
    <span>{{ $environmentLabel }}</span>
    <span>Build {{ $build }}</span>
    @if (filled($organizationName))
        <span>{{ $organizationName }}</span>
    @endif
    <span>&copy; {{ now()->year }}</span>
</footer>
