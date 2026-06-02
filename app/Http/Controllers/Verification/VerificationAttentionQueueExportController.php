<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Support\VerificationAttentionQueueExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificationAttentionQueueExportController extends Controller
{
    public function excel(Request $request): Response
    {
        abort_unless(auth()->user()?->canAccessSaasRevenueOperations(), 403);

        $filter = $this->resolveFilter($request);
        $rows = VerificationAttentionQueueExport::rows($filter);
        $meta = VerificationAttentionQueueExport::meta($filter);

        return response()->streamDownload(
            fn () => print(VerificationAttentionQueueExport::excelHtml($rows, $meta)),
            'verification-attention-queue.xls',
            ['Content-Type' => 'application/vnd.ms-excel']
        );
    }

    public function pdf(Request $request): Response
    {
        abort_unless(auth()->user()?->canAccessSaasRevenueOperations(), 403);

        $filter = $this->resolveFilter($request);
        $rows = VerificationAttentionQueueExport::rows($filter);
        $meta = VerificationAttentionQueueExport::meta($filter);

        return response()->streamDownload(
            fn () => print(VerificationAttentionQueueExport::pdf($rows, $meta)),
            'verification-attention-queue.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function resolveFilter(Request $request): ?string
    {
        $filter = $request->query('attention_filter');

        return filled($filter) ? (string) $filter : null;
    }
}
