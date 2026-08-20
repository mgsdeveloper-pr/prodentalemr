<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItemAttachment;
use App\Services\Documents\BillingWorkItemAttachmentService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingWorkItemAttachmentController extends Controller
{
    public function preview(
        BillingWorkItemAttachment $attachment,
        BillingWorkItemAttachmentService $attachments,
    ): BinaryFileResponse {
        $this->authorizeAttachment($attachment, $attachments);

        return $attachments->previewResponse($attachment);
    }

    public function download(
        BillingWorkItemAttachment $attachment,
        BillingWorkItemAttachmentService $attachments,
    ): BinaryFileResponse {
        $this->authorizeAttachment($attachment, $attachments);

        return $attachments->downloadResponse($attachment, auth()->user());
    }

    protected function authorizeAttachment(
        BillingWorkItemAttachment $attachment,
        BillingWorkItemAttachmentService $attachments,
    ): void {
        abort_unless(auth()->user()?->can('download', $attachment), 403);
        abort_unless(! $attachment->trashed(), 404);
        abort_unless($attachments->exists($attachment), 404);
    }
}
