<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItemAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingWorkItemAttachmentController extends Controller
{
    public function download(BillingWorkItemAttachment $attachment): BinaryFileResponse
    {
        abort_unless(auth()->user()?->canAccessSaasRevenueOperations(), 403);
        abort_unless(! $attachment->trashed(), 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $attachment->workItem?->recordActivity('attachment_downloaded', 'An attachment was downloaded.', [
            'panel' => 'verification',
            'original_file_name' => $attachment->original_file_name,
            'mime_type' => $attachment->mime_type,
            'user_name' => auth()->user()?->name,
        ]);

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->original_file_name ?: basename($attachment->file_path),
        );
    }
}
