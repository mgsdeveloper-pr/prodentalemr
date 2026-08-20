<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItem;
use App\Models\VerificationFormSubmission;
use App\Models\VerificationPdfPreset;
use App\Services\Verification\DeliveryService;
use App\Services\Verification\PDFService;
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

        $preset = $this->resolvePreset($request, $billingWorkItem);
        $mode = $this->resolveMode($request, $billingWorkItem, $preset);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode, $preset);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode, $preset);
        $showBlankRows = $this->resolveShowBlankRows($request, $mode, $preset);
        $submission = $this->resolveSubmission($request, $billingWorkItem);
        $this->recordPdfActivity($billingWorkItem, 'downloaded', 'admin', $mode);

        return response()->streamDownload(
            fn () => print(app(PDFService::class)->output($billingWorkItem, $mode, $sections, $questionIds, $showBlankRows, $submission)),
            app(PDFService::class)->fileName($billingWorkItem, $mode, $submission),
            $this->pdfHeaders('attachment; filename="' . app(PDFService::class)->fileName($billingWorkItem, $mode, $submission) . '"'),
        );
    }

    public function previewForAdmin(Request $request, BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureAdminCanAccess($billingWorkItem);

        $preset = $this->resolvePreset($request, $billingWorkItem);
        $mode = $this->resolveMode($request, $billingWorkItem, $preset);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode, $preset);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode, $preset);
        $showBlankRows = $this->resolveShowBlankRows($request, $mode, $preset);
        $submission = $this->resolveSubmission($request, $billingWorkItem);
        $this->recordPdfActivity($billingWorkItem, 'previewed', 'admin', $mode);

        return response(
            app(PDFService::class)->output($billingWorkItem, $mode, $sections, $questionIds, $showBlankRows, $submission),
            200,
            $this->pdfHeaders('inline; filename="' . app(PDFService::class)->fileName($billingWorkItem, $mode, $submission) . '"'),
        );
    }

    public function downloadForClinic(Request $request, BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureClinicCanAccess($billingWorkItem);

        $preset = $this->resolvePreset($request, $billingWorkItem);
        $mode = $this->resolveMode($request, $billingWorkItem, $preset);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode, $preset);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode, $preset);
        $showBlankRows = $this->resolveShowBlankRows($request, $mode, $preset);
        $submission = $this->resolveSubmission($request, $billingWorkItem);
        $this->recordPdfActivity($billingWorkItem, 'downloaded', 'clinic', $mode);

        return response()->streamDownload(
            fn () => print(app(PDFService::class)->output($billingWorkItem, $mode, $sections, $questionIds, $showBlankRows, $submission)),
            app(PDFService::class)->fileName($billingWorkItem, $mode, $submission),
            $this->pdfHeaders('attachment; filename="' . app(PDFService::class)->fileName($billingWorkItem, $mode, $submission) . '"'),
        );
    }

    public function previewForClinic(Request $request, BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureClinicCanAccess($billingWorkItem);

        $preset = $this->resolvePreset($request, $billingWorkItem);
        $mode = $this->resolveMode($request, $billingWorkItem, $preset);
        $sections = $this->resolveSections($request, $billingWorkItem, $mode, $preset);
        $questionIds = $this->resolveQuestionIds($request, $billingWorkItem, $mode, $preset);
        $showBlankRows = $this->resolveShowBlankRows($request, $mode, $preset);
        $submission = $this->resolveSubmission($request, $billingWorkItem);
        $this->recordPdfActivity($billingWorkItem, 'previewed', 'clinic', $mode);

        return response(
            app(PDFService::class)->output($billingWorkItem, $mode, $sections, $questionIds, $showBlankRows, $submission),
            200,
            $this->pdfHeaders('inline; filename="' . app(PDFService::class)->fileName($billingWorkItem, $mode, $submission) . '"'),
        );
    }

    protected function ensureAdminCanAccess(BillingWorkItem $billingWorkItem): void
    {
        $user = auth()->user();

        abort_unless($user?->canAccessVerificationWorkspace(), 403);
        abort_unless($user?->can('view', $billingWorkItem), 403);

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
        app(DeliveryService::class)->recordPdfAccess($billingWorkItem, $action, $panel, $mode, auth()->user());
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

    protected function resolvePreset(Request $request, BillingWorkItem $billingWorkItem): ?VerificationPdfPreset
    {
        $presetId = $request->integer('preset_id');

        if (! $presetId || ! $billingWorkItem->clinic_id) {
            return null;
        }

        return VerificationPdfPreset::query()
            ->where('clinic_id', $billingWorkItem->clinic_id)
            ->where('is_active', true)
            ->whereKey($presetId)
            ->first();
    }

    protected function resolveMode(Request $request, BillingWorkItem $billingWorkItem, ?VerificationPdfPreset $preset = null): string
    {
        $configuredMode = $preset?->getOutputMode() ?: $billingWorkItem->clinic?->getVerificationPdfOutputMode();
        $mode = $request->filled('mode')
            ? (string) $request->input('mode')
            : (string) ($configuredMode ?: 'standard');

        return VerificationResultPdf::normalizeOutputMode($mode);
    }

    protected function resolveSections(Request $request, BillingWorkItem $billingWorkItem, string $mode, ?VerificationPdfPreset $preset = null): array
    {
        $configuredSections = $preset?->getSectionKeys() ?? $billingWorkItem->clinic?->getVerificationPdfOutputSections() ?? [];

        if (! VerificationResultPdf::isCustomOutputMode($mode)) {
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

    protected function resolveQuestionIds(Request $request, BillingWorkItem $billingWorkItem, string $mode, ?VerificationPdfPreset $preset = null): array
    {
        if (! VerificationResultPdf::isCustomOutputMode($mode)) {
            return [];
        }

        $configuredQuestionIds = $preset?->getQuestionIds() ?? $billingWorkItem->clinic?->getVerificationPdfOutputQuestionIds() ?? [];
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

    protected function resolveShowBlankRows(Request $request, string $mode, ?VerificationPdfPreset $preset = null): bool
    {
        if ($preset) {
            return $preset->shouldShowBlankRows();
        }

        if ($request->has('show_blank_rows')) {
            return $request->boolean('show_blank_rows');
        }

        return $mode === 'standard';
    }

    protected function resolveSubmission(Request $request, BillingWorkItem $billingWorkItem): ?VerificationFormSubmission
    {
        $submissionId = $request->integer('submission_id');

        if (! $submissionId) {
            return null;
        }

        return $billingWorkItem->formSubmissions()
            ->where('status', BillingWorkItem::STATUS_DONE)
            ->findOrFail($submissionId);
    }
}
