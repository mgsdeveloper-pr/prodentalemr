@php
    $user = auth()->user();
    $panel = $panel ?? 'verification';
    $clinicId = $clinicId ?? null;
    $notifications = \App\Support\VerificationNotificationCenter::topbarNotificationsFor($panel, $user, $clinicId, 8);
    $unreadCount = \App\Support\VerificationNotificationCenter::unreadCountFor($panel, $user, $clinicId);
    $alertNotification = \App\Support\VerificationNotificationCenter::topbarAlertFor($panel, $user, $clinicId);
    $notificationCentreUrl = $panel === 'verification'
        ? \App\Filament\Admin\Pages\VerificationNotificationCentre::getUrl()
        : \App\Filament\Clinic\Pages\VerificationNotificationCentre::getUrl();
    $markAllRoute = $panel === 'verification'
        ? route('admin.verification-notifications.read-all')
        : route('clinic.verification-notifications.read-all');
@endphp

<div
    x-data="{
        open: false,
        alertOpen: false,
        dismissAlert(key) {
            this.alertOpen = false;
            if (key) {
                localStorage.setItem(key, '1');
            }
        },
    }"
    class="fi-topbar-item relative"
    style="display: flex; align-items: center;"
>
    <button
        type="button"
        x-on:click="open = ! open"
        class="fi-icon-btn fi-color-gray fi-size-lg"
        style="position: relative; display: inline-flex; height: 2.75rem; width: 2.75rem; align-items: center; justify-content: center; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);"
        title="Verification notifications"
    >
        <svg xmlns="http://www.w3.org/2000/svg" style="height: 1.35rem; width: 1.35rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 1-5.714 0M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>

        @if ($unreadCount > 0)
            <span style="position: absolute; top: -0.1rem; right: -0.1rem; min-width: 1.15rem; height: 1.15rem; padding: 0 0.3rem; border-radius: 999px; background: #dc2626; color: #ffffff; font-size: 11px; font-weight: 700; line-height: 1.15rem; text-align: center;">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-on:click.away="open = false"
        x-transition.opacity.scale.origin.top.right
        style="position: absolute; right: 0; top: calc(100% + 12px); z-index: 60; width: min(28rem, calc(100vw - 2rem)); overflow: hidden; border-radius: 24px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.16);"
    >
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid #e7eef7;">
            <div>
                <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Notification Centre</div>
                <div style="margin-top: 4px; font-size: 15px; font-weight: 700; color: #0f172a;">Verification alerts</div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <form method="POST" action="{{ $markAllRoute }}">
                    @csrf
                    <button type="submit" style="border: 0; background: transparent; font-size: 13px; font-weight: 700; color: #2563eb;">Mark all read</button>
                </form>
                <a href="{{ $notificationCentreUrl }}" style="font-size: 13px; font-weight: 700; color: #0f172a;">View all</a>
            </div>
        </div>

        <div style="max-height: 28rem; overflow-y: auto; padding: 10px;">
            @forelse ($notifications as $notification)
                @php
                    $openRoute = $panel === 'verification'
                        ? route('admin.verification-notifications.open', $notification)
                        : route('clinic.verification-notifications.open', $notification);
                    $readRoute = $panel === 'verification'
                        ? route('admin.verification-notifications.read', $notification)
                        : route('clinic.verification-notifications.read', $notification);
                    $accent = match ($notification->level) {
                        'danger' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'dot' => '#dc2626'],
                        'warning' => ['bg' => '#fff7ed', 'border' => '#fed7aa', 'dot' => '#ea580c'],
                        'success' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'dot' => '#16a34a'],
                        default => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'dot' => '#2563eb'],
                    };
                @endphp

                <div style="margin-bottom: 10px; border-radius: 18px; border: 1px solid {{ $accent['border'] }}; background: {{ $accent['bg'] }}; padding: 14px 14px 12px;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                        <div style="display: flex; gap: 10px; min-width: 0;">
                            <span style="margin-top: 6px; display: inline-block; height: 10px; width: 10px; flex-shrink: 0; border-radius: 999px; background: {{ $accent['dot'] }};"></span>
                            <div style="min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $notification->title }}</span>
                                    @if (blank($notification->read_at))
                                        <span style="display: inline-flex; align-items: center; border-radius: 999px; background: #ffffff; padding: 3px 8px; font-size: 11px; font-weight: 800; color: #0f172a;">New</span>
                                    @endif
                                </div>
                                <div style="margin-top: 5px; font-size: 13px; line-height: 1.55; color: #475569;">{{ $notification->message }}</div>
                                <div style="margin-top: 8px; font-size: 12px; color: #64748b;">{{ $notification->created_at?->diffForHumans() }}</div>
                            </div>
                        </div>

                        @if (blank($notification->read_at))
                            <form method="POST" action="{{ $readRoute }}">
                                @csrf
                                <button type="submit" style="border: 0; background: transparent; font-size: 12px; font-weight: 700; color: #0f172a;">Read</button>
                            </form>
                        @endif
                    </div>

                    <div style="margin-top: 10px; display: flex; justify-content: flex-end;">
                        <a href="{{ $openRoute }}" style="display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; background: #0f172a; padding: 8px 12px; font-size: 12px; font-weight: 700; color: #ffffff; text-decoration: none;">
                            Open
                        </a>
                    </div>
                </div>
            @empty
                <div style="padding: 22px 14px; text-align: center; color: #64748b; font-size: 14px;">
                    No verification notifications right now.
                </div>
            @endforelse
        </div>
    </div>

    @if ($alertNotification)
        @php
            $alertReadRoute = $panel === 'verification'
                ? route('admin.verification-notifications.read', $alertNotification)
                : route('clinic.verification-notifications.read', $alertNotification);
            $alertDismissKey = 'verification-alert-dismissed:' . $panel . ':' . $alertNotification->getKey();
        @endphp

        <div
            x-cloak
            x-init="alertOpen = localStorage.getItem(@js($alertDismissKey)) !== '1'"
            x-show="alertOpen"
            x-transition.opacity.scale.origin.top.right
            style="position: fixed; right: 24px; top: 92px; z-index: 70; width: min(26rem, calc(100vw - 2rem)); overflow: hidden; border-radius: 24px; border: 1px solid #fecaca; background: linear-gradient(135deg, #fff7ed 0%, #fef2f2 100%); box-shadow: 0 28px 64px rgba(15, 23, 42, 0.16);"
        >
            <div style="padding: 18px 18px 14px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                    <div>
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #b91c1c;">Alert</div>
                        <div style="margin-top: 4px; font-size: 18px; font-weight: 800; color: #0f172a;">{{ $alertNotification->title }}</div>
                    </div>

                    <button type="button" x-on:click="dismissAlert(@js($alertDismissKey))" style="border: 0; background: transparent; font-size: 20px; line-height: 1; color: #64748b;">&times;</button>
                </div>

                <div style="margin-top: 10px; font-size: 14px; line-height: 1.6; color: #334155;">
                    {{ $alertNotification->message }}
                </div>

                <div style="margin-top: 16px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" x-on:click="dismissAlert(@js($alertDismissKey))" style="border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; padding: 10px 14px; font-size: 13px; font-weight: 700; color: #0f172a;">Dismiss</button>
                    <form method="POST" action="{{ $alertReadRoute }}" x-on:submit="dismissAlert(@js($alertDismissKey))">
                        @csrf
                        <button type="submit" style="border-radius: 12px; background: #dc2626; padding: 10px 14px; font-size: 13px; font-weight: 700; color: #ffffff;">Mark read</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
