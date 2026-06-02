<?php

namespace App\Http\Controllers\Verification;

use App\Models\VerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationNotificationActionController
{
    public function open(Request $request, VerificationNotification $notification): RedirectResponse
    {
        abort_unless($this->canAccess($request, $notification), 403);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return redirect()->to($notification->target_url ?: url()->previous());
    }

    public function markRead(Request $request, VerificationNotification $notification): RedirectResponse
    {
        abort_unless($this->canAccess($request, $notification), 403);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return back();
    }

    public function markAllRead(Request $request, string $panel): RedirectResponse
    {
        abort_unless(in_array($panel, ['verification', 'clinic'], true), 404);

        VerificationNotification::query()
            ->where('user_id', auth()->id())
            ->where('panel', $panel)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    protected function canAccess(Request $request, VerificationNotification $notification): bool
    {
        $panel = $request->route('panel') ?: ($request->is('verification/*') ? 'verification' : 'clinic');

        return $notification->user_id === auth()->id() && $notification->panel === $panel;
    }
}
