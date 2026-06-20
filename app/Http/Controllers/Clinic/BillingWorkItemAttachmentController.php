<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItemAttachment;
use App\Support\ClinicPanelScope;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingWorkItemAttachmentController extends Controller
{
    public function preview(BillingWorkItemAttachment $attachment): BinaryFileResponse
    {
        $this->authorizeAttachment($attachment);

        return response()->file(
            Storage::disk('local')->path($attachment->file_path),
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_file_name ?: basename($attachment->file_path)) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function download(BillingWorkItemAttachment $attachment): BinaryFileResponse
    {
        $this->authorizeAttachment($attachment);

        $attachment->workItem?->recordActivity('attachment_downloaded', 'An attachment was downloaded.', [
            'panel' => 'clinic',
            'original_file_name' => $attachment->original_file_name,
            'mime_type' => $attachment->mime_type,
            'user_name' => auth()->user()?->name,
        ]);

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->original_file_name ?: basename($attachment->file_path),
        );
    }

    protected function authorizeAttachment(BillingWorkItemAttachment $attachment): void
    {
        $user = auth()->user();

        abort_unless($user?->canAccessClinicVerificationRequests(), 403);
        abort_unless(! $attachment->trashed(), 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $workItem = $attachment->workItem;
        abort_unless($workItem, 404);

        if ($user?->shouldBypassClinicScope()) {
            $selectedClinicId = ClinicPanelScope::selectedClinicId();

            abort_unless($selectedClinicId && (int) $workItem->clinic_id === (int) $selectedClinicId, 403);
        } else {
            abort_unless(
                (int) $workItem->organization_id === (int) $user->organization_id
                && (int) $workItem->clinic_id === (int) $user->clinic_id,
                403
            );
        }
    }
}
