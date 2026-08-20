@php
    $user = auth()->user();
    $displayName = trim((string) ($user?->name ?? 'User'));
    $firstName = explode(' ', $displayName)[0] ?: 'User';
    $nameParts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY) ?: ['U'];
    $initials = collect(array_slice($nameParts, 0, 2))
        ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $roleLabel = trim((string) ($user?->getPrimaryRoleLabel() ?? 'User'));
    $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId();
    $signOutRoute = match ($panelId) {
        'saas' => 'saas.signout',
        'admin' => 'admin.signout',
        'clinic' => 'clinic.signout',
        'dso' => 'dso.signout',
        default => null,
    };
    $signOutUrl = $signOutRoute !== null && \Illuminate\Support\Facades\Route::has($signOutRoute)
        ? route($signOutRoute)
        : url('/logout');
@endphp

<div class="app-sidebar-user-footer" aria-label="Signed in user">
    <div class="app-sidebar-user-footer__identity">
        <div class="app-sidebar-user-footer__avatar" aria-hidden="true">{{ $initials }}</div>
        <div class="app-sidebar-user-footer__body">
            <span class="app-sidebar-user-footer__name">Hi, {{ $firstName }}</span>
            <span class="app-sidebar-user-footer__role">{{ $roleLabel }}</span>
        </div>
    </div>

    <div class="app-sidebar-user-footer__actions" aria-label="Account actions">
        <a
            class="app-sidebar-user-footer__action"
            href="{{ route('profile.edit') }}"
            aria-label="Settings"
            title="Settings"
        >
            @svg('heroicon-o-cog-6-tooth', 'app-sidebar-user-footer__action-icon', ['aria-hidden' => 'true'])
        </a>
        <a
            class="app-sidebar-user-footer__action app-sidebar-user-footer__action--logout"
            href="{{ $signOutUrl }}"
            aria-label="Logout"
            title="Logout"
        >
            @svg('heroicon-o-arrow-left-start-on-rectangle', 'app-sidebar-user-footer__action-icon', ['aria-hidden' => 'true'])
        </a>
    </div>
</div>
