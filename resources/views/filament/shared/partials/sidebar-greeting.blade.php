@php
    $user = auth()->user();
    $displayName = trim((string) ($user?->name ?? 'User'));
    $nameParts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY) ?: ['U'];
    $initials = collect(array_slice($nameParts, 0, 2))
        ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $roleLabel = trim((string) ($user?->getPrimaryRoleLabel() ?? 'User'));
@endphp

<div class="app-sidebar-greeting">
    <div class="app-sidebar-greeting__avatar" aria-hidden="true">{{ $initials }}</div>
    <div class="app-sidebar-greeting__body">
        <span class="app-sidebar-greeting__hello">Hi, {{ explode(' ', $displayName)[0] ?: 'User' }}</span>
        <span class="app-sidebar-greeting__role">{{ $roleLabel }}</span>
    </div>
</div>
