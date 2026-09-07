<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItem;
use App\Models\TelephonyCall;
use App\Support\TelephonyAccess;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelephonyRecordingController extends Controller
{
    public function __invoke(
        Request $request,
        BillingWorkItem $billingWorkItem,
        TelephonyCall $telephonyCall,
    ): StreamedResponse {
        abort_unless((int) $telephonyCall->billing_work_item_id === (int) $billingWorkItem->getKey(), 404);
        abort_unless($request->user()?->can('view', $billingWorkItem), 403);
        abort_unless(TelephonyAccess::canAccessRecording($request->user(), $telephonyCall), 403);

        $recordingUrl = TelephonyCall::normalizeMightyCallRecordingUrl($telephonyCall->recording_url);
        abort_unless($recordingUrl, 404);

        try {
            $upstream = $this->fetchRecording($request, $recordingUrl);
        } catch (ConnectionException) {
            abort(502, 'The recording is temporarily unavailable from MightyCall.');
        }

        abort_unless($upstream->successful(), 502, 'The recording is temporarily unavailable from MightyCall.');

        $contentType = strtolower((string) $upstream->header('Content-Type'));
        abort_unless(str_starts_with($contentType, 'audio/') || str_starts_with($contentType, 'application/octet-stream'), 502, 'MightyCall did not return a playable recording.');

        if (! $request->headers->has('Range') || str_starts_with((string) $request->header('Range'), 'bytes=0-')) {
            $billingWorkItem->recordActivity('insurance_call_recording_played', 'Insurance call recording opened.', [
                'call_public_id' => $telephonyCall->public_id,
                'provider_call_id' => $telephonyCall->provider_call_id,
                'user_name' => $request->user()?->name,
            ]);
        }

        $body = $upstream->toPsrResponse()->getBody();
        $headers = [
            'Content-Type' => $upstream->header('Content-Type') ?: 'audio/mpeg',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        foreach (['Content-Length', 'Content-Range', 'Accept-Ranges'] as $header) {
            if (filled($upstream->header($header))) {
                $headers[$header] = $upstream->header($header);
            }
        }

        return response()->stream(function () use ($body): void {
            while (! $body->eof()) {
                echo $body->read(64 * 1024);

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            }

            $body->close();
        }, $upstream->status(), $headers);
    }

    private function fetchRecording(Request $request, string $url): HttpResponse
    {
        $headers = ['Accept' => 'audio/*, application/octet-stream'];

        if ($request->headers->has('Range')) {
            $headers['Range'] = (string) $request->header('Range');
        }

        return Http::withHeaders($headers)
            ->connectTimeout(10)
            ->timeout(60)
            ->withOptions([
                'allow_redirects' => [
                    'max' => 3,
                    'strict' => true,
                    'referer' => false,
                    'protocols' => ['https'],
                ],
                'stream' => true,
            ])
            ->get($url);
    }
}
