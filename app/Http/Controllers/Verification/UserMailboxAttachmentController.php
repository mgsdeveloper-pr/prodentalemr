<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Support\UserMailboxService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserMailboxAttachmentController extends Controller
{
    public function __invoke(Request $request, UserMailboxService $service): StreamedResponse
    {
        abort_unless(auth()->user()?->canAccessVerificationWorkspace(), 403);

        $mailbox = $service->mailbox(auth()->user());
        abort_unless($mailbox && $service->isConfigured($mailbox), 404);

        $attachment = $service->downloadAttachment(
            $mailbox,
            (string) $request->query('folder'),
            (string) $request->query('uid'),
            (string) $request->query('part'),
        );

        abort_unless($attachment, 404);

        return response()->streamDownload(
            function () use ($attachment): void {
                echo $attachment['content'];
            },
            $attachment['name'],
            ['Content-Type' => $attachment['mime']]
        );
    }
}
