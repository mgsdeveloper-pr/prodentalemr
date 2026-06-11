<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Support\UserMailboxService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMailboxMessagePreviewController extends Controller
{
    public function __invoke(Request $request, UserMailboxService $service): Response
    {
        abort_unless(auth()->user()?->canAccessVerificationWorkspace(), 403);

        $mailbox = $service->mailbox(auth()->user());
        abort_unless($mailbox && $service->isConfigured($mailbox), 404);

        $message = $service->fetchMessage(
            $mailbox,
            (string) $request->query('folder'),
            (string) $request->query('uid'),
        );

        abort_unless($message, 404);

        $html = $message['body_html']
            ?: nl2br(e((string) ($message['body_text'] ?? '')));

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
