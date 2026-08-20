<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItemAttachment;
use App\Services\Documents\BillingWorkItemAttachmentService;
use App\Support\BillingWorkItemAttachmentSupportAudit;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingWorkItemAttachmentController extends Controller
{
    public function preview(
        BillingWorkItemAttachment $attachment,
        BillingWorkItemAttachmentService $attachments,
    ): BinaryFileResponse {
        $this->authorizeAttachment($attachment, $attachments);
        BillingWorkItemAttachmentSupportAudit::recordAccess('support_document_previewed', $attachment);

        return $attachments->previewResponse($attachment);
    }

    public function download(
        BillingWorkItemAttachment $attachment,
        BillingWorkItemAttachmentService $attachments,
    ): BinaryFileResponse {
        $this->authorizeAttachment($attachment, $attachments);
        BillingWorkItemAttachmentSupportAudit::recordAccess('support_document_downloaded', $attachment);

        return $attachments->downloadResponse($attachment, auth()->user());
    }

    protected function authorizeAttachment(
        BillingWorkItemAttachment $attachment,
        BillingWorkItemAttachmentService $attachments,
    ): void {
        abort_unless(auth()->user()?->can('download', $attachment), 403);
        abort_unless(! $attachment->trashed(), 404);
        abort_unless($attachments->exists($attachment), 404);
        abort_unless(BillingWorkItemAttachmentSupportAudit::canAccess($attachment), 403);
    }
}
