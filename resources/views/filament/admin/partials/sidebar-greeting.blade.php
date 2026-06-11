@php
    $user = auth()->user();
    $displayName = trim((string) ($user?->name ?? 'User'));
    $nameParts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY) ?: ['U'];
    $initials = collect(array_slice($nameParts, 0, 2))
        ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $roleLabel = trim((string) ($user?->getPrimaryRoleLabel() ?? 'Verification User'));
@endphp

<div class="admin-sidebar-greeting">
    <div class="admin-sidebar-greeting__avatar" aria-hidden="true">{{ $initials }}</div>
    <div class="admin-sidebar-greeting__body">
        <span class="admin-sidebar-greeting__name">{{ $displayName }}</span>
        <span class="admin-sidebar-greeting__role">{{ $roleLabel }}</span>
    </div>
</div>
