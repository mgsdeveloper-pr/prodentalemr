<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Models\VerificationInboxMessage;
use Illuminate\Http\Response;

class VerificationInboxMessagePreviewController extends Controller
{
    public function __invoke(VerificationInboxMessage $message): Response
    {
        abort_unless(auth()->user()?->canAccessVerificationWorkspace(), 403);
        abort_unless(
            auth()->user()?->hasFullVerificationClinicAccess()
            || auth()->user()?->canAccessVerificationClinic($message->clinic_id),
            403
        );

        return response($message->sanitizedHtmlBody(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
