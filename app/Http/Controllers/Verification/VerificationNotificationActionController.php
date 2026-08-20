<?php

namespace App\Http\Controllers\Verification;

use App\Models\VerificationNotification;
use App\Services\Notifications\VerificationNotificationService;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationNotificationActionController
{
    public function open(
        Request $request,
        VerificationNotification $notification,
        VerificationNotificationService $notifications,
    ): RedirectResponse {
        abort_unless($this->canAccess($request, $notification) && auth()->user()?->can('update', $notification), 403);

        $notifications->markRead($notification);

        return redirect()->to($notification->target_url ?: url()->previous());
    }

    public function markRead(
        Request $request,
        VerificationNotification $notification,
        VerificationNotificationService $notifications,
    ): RedirectResponse {
        abort_unless($this->canAccess($request, $notification) && auth()->user()?->can('update', $notification), 403);

        $notifications->markRead($notification);

        return back();
    }

    public function markAllRead(
        Request $request,
        string $panel,
        VerificationNotificationService $notifications,
    ): RedirectResponse {
        abort_unless(in_array($panel, ['verification', 'clinic'], true), 404);

        $clinicId = $panel === 'verification'
            ? AdminClinicScope::selectedClinicId()
            : ClinicPanelScope::selectedClinicId();

        $notifications->markAllReadForPanel($request->user(), $panel, $clinicId);

        return back();
    }

    protected function canAccess(Request $request, VerificationNotification $notification): bool
    {
        $panel = $request->route('panel') ?: ($request->is('verification/*') ? 'verification' : 'clinic');

        return $notification->user_id === auth()->id() && $notification->panel === $panel;
    }
}
