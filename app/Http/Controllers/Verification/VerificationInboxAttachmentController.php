<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Models\VerificationInboxAttachment;
use Illuminate\Support\Facades\Storage;

class VerificationInboxAttachmentController extends Controller
{
    public function __invoke(VerificationInboxAttachment $attachment)
    {
        abort_unless(auth()->user()?->canAccessVerificationWorkspace(), 403);
        abort_unless(
            auth()->user()?->hasFullVerificationClinicAccess()
            || auth()->user()?->canAccessVerificationClinic($attachment->message?->clinic_id),
            403
        );
        abort_unless($attachment->isAvailable(), 404);

        return Storage::disk($attachment->storage_disk ?: 'verification_inbox')->download(
            $attachment->storage_path,
            $attachment->file_name
        );
    }
}
