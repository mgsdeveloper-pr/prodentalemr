<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;
use App\Support\VerificationResultPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificationResultPdfController extends Controller
{
    public function downloadForAdmin(Request $request, BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureAdminCanAccess($billingWorkItem);

        $mode = $this->resolveMode($request, $billingWorkItem);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode);
        $this->recordPdfActivity($billingWorkItem, 'downloaded', 'admin', $mode);

        return response()->streamDownload(
            fn () => print(VerificationResultPdf::output($billingWorkItem, $mode, $sections, $questionIds)),
            VerificationResultPdf::fileName($billingWorkItem, $mode),
            $this->pdfHeaders('attachment; filename="' . VerificationResultPdf::fileName($billingWorkItem, $mode) . '"'),
        );
    }

    public function previewForAdmin(Request $request, BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureAdminCanAccess($billingWorkItem);

        $mode = $this->resolveMode($request, $billingWorkItem);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode);
        $this->recordPdfActivity($billingWorkItem, 'previewed', 'admin', $mode);

        return response(
            VerificationResultPdf::output($billingWorkItem, $mode, $sections, $questionIds),
            200,
            $this->pdfHeaders('inline; filename="' . VerificationResultPdf::fileName($billingWorkItem, $mode) . '"'),
        );
    }

    public function downloadForClinic(Request $request, BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureClinicCanAccess($billingWorkItem);

        $mode = $this->resolveMode($request, $billingWorkItem);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode);
        $this->recordPdfActivity($billingWorkItem, 'downloaded', 'clinic', $mode);

        return response()->streamDownload(
            fn () => print(VerificationResultPdf::output($billingWorkItem, $mode, $sections, $questionIds)),
            VerificationResultPdf::fileName($billingWorkItem, $mode),
            $this->pdfHeaders('attachment; filename="' . VerificationResultPdf::fileName($billingWorkItem, $mode) . '"'),
        );
    }

    public function previewForClinic(Request $request, BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureClinicCanAccess($billingWorkItem);

        $mode = $this->resolveMode($request, $billingWorkItem);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode);
        $this->recordPdfActivity($billingWorkItem, 'previewed', 'clinic', $mode);

        return response(
            VerificationResultPdf::output($billingWorkItem, $mode, $sections, $questionIds),
            200,
            $this->pdfHeaders('inline; filename="' . VerificationResultPdf::fileName($billingWorkItem, $mode) . '"'),
        );
    }

    protected function ensureAdminCanAccess(BillingWorkItem $billingWorkItem): void
    {
        abort_unless(auth()->user()?->canAccessSaasRevenueOperations(), 403);

        $selectedClinicId = AdminClinicScope::selectedClinicId();

        if ($selectedClinicId) {
            abort_unless((int) $billingWorkItem->clinic_id === (int) $selectedClinicId, 403);
        }
    }

    protected function ensureClinicCanAccess(BillingWorkItem $billingWorkItem): void
    {
        $user = auth()->user();

        abort_unless($user?->canAccessClinicVerificationRequests(), 403);

        if ($user?->shouldBypassClinicScope()) {
            $selectedClinicId = ClinicPanelScope::selectedClinicId();
            abort_unless($selectedClinicId && (int) $billingWorkItem->clinic_id === (int) $selectedClinicId, 403);

            return;
        }

        abort_unless(
            (int) $billingWorkItem->organization_id === (int) $user->organization_id
            && (int) $billingWorkItem->clinic_id === (int) $user->clinic_id,
            403
        );
    }

    protected function recordPdfActivity(BillingWorkItem $billingWorkItem, string $action, string $panel, string $mode): void
    {
        $billingWorkItem->recordActivity('verification_pdf_' . $action, 'Verification PDF ' . $action . '.', [
            'panel' => $panel,
            'output_mode' => $mode,
            'user_name' => auth()->user()?->name,
        ]);
    }

    protected function pdfHeaders(string $disposition): array
    {
        return [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    protected function resolveMode(Request $request, BillingWorkItem $billingWorkItem): string
    {
        $configuredMode = $billingWorkItem->clinic?->getVerificationPdfOutputMode();
        $mode = (string) ($configuredMode ?: $request->string('mode', 'standard'));

        return array_key_exists($mode, VerificationResultPdf::OUTPUT_MODE_OPTIONS) ? $mode : 'standard';
    }

    protected function resolveSections(Request $request, BillingWorkItem $billingWorkItem, string $mode): array
    {
        $configuredSections = $billingWorkItem->clinic?->getVerificationPdfOutputSections() ?? [];

        if ($mode !== 'selected') {
            return [];
        }

        $sections = ! empty($configuredSections)
            ? $configuredSections
            : $request->input('sections', []);

        if (! is_array($sections)) {
            return [];
        }

        return array_values(array_filter($sections, fn ($section): bool => is_string($section) && $section !== ''));
    }

    protected function resolveQuestionIds(Request $request, BillingWorkItem $billingWorkItem, string $mode): array
    {
        if ($mode !== 'selected') {
            return [];
        }

        $configuredQuestionIds = $billingWorkItem->clinic?->getVerificationPdfOutputQuestionIds() ?? [];
        $questionIds = ! empty($configuredQuestionIds)
            ? $configuredQuestionIds
            : $request->input('question_ids', []);

        if (! is_array($questionIds)) {
            return [];
        }

        return array_values(array_filter(
            $questionIds,
            fn ($questionId): bool => is_numeric($questionId) && (int) $questionId > 0
        ));
    }
}
