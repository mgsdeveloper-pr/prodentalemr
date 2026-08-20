<?php

namespace App\Services\Documents;

use App\Models\BillingWorkItemAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingWorkItemAttachmentService
{
    public function exists(BillingWorkItemAttachment $attachment): bool
    {
        return filled($attachment->file_path)
            && Storage::disk('local')->exists($attachment->file_path);
    }

    public function previewResponse(BillingWorkItemAttachment $attachment): BinaryFileResponse
    {
        return response()->file(
            Storage::disk('local')->path($attachment->file_path),
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($this->fileName($attachment)) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function downloadResponse(BillingWorkItemAttachment $attachment, ?User $user = null): BinaryFileResponse
    {
        $attachment->workItem?->recordActivity('attachment_downloaded', 'An attachment was downloaded.', [
            'panel' => 'verification',
            'original_file_name' => $attachment->original_file_name,
            'mime_type' => $attachment->mime_type,
            'user_name' => $user?->name,
        ]);

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $this->fileName($attachment),
        );
    }

    protected function fileName(BillingWorkItemAttachment $attachment): string
    {
        return $attachment->original_file_name ?: basename($attachment->file_path);
    }
}
