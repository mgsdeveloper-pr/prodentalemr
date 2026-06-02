<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use Illuminate\Support\Facades\Route;

class VerificationReadinessReport
{
    public static function summary(): array
    {
        return [
            'questions' => VerificationFormQuestion::query()->count(),
            'requests' => BillingWorkItem::query()
                ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification'))
                ->count(),
            'clinics_with_pdf_template' => Clinic::query()
                ->where(function ($query): void {
                    $query->where('verification_pdf_output_mode', '!=', 'standard')
                        ->orWhereNotNull('verification_pdf_output_sections')
                        ->orWhereNotNull('verification_pdf_output_question_ids');
                })
                ->count(),
            'workspace_enabled_enrollments' => ClientServiceEnrollment::query()
                ->where('status', 'active')
                ->where('clinic_workspace_enabled', true)
                ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification'))
                ->count(),
        ];
    }

    public static function sections(): array
    {
        return [
            [
                'title' => 'Done',
                'tone' => 'success',
                'items' => [
                    self::item(
                        'done',
                        'Clinic verification import is available',
                        Route::has('filament.clinic.resources.verification-requests.import')
                            ? 'Clinic-side bulk import is wired and available from the Insurance Verification queue.'
                            : 'Clinic-side bulk import route is missing.'
                    ),
                    self::item(
                        'done',
                        'Admin verification import is available',
                        Route::has('filament.admin.resources.verifications.import')
                            ? 'Admin-side bulk import is now available for agency/internal teams.'
                            : 'Admin-side bulk import route is missing.'
                    ),
                    self::item(
                        'done',
                        'Verification questions are section-wise in Clinic and Admin',
                        'Both panels now expose section-aware question management instead of plain question lists.'
                    ),
                    self::item(
                        'done',
                        'Clinic PDF template settings are centralized',
                        'Clinic-level PDF output mode, section selection, and sub-question selection are stored and reused across Clinic and Admin.'
                    ),
                    self::item(
                        'done',
                        'Clinic/Admin form parity is in place',
                        'Clinic verification now follows the same queue/form/view pattern as Admin wherever business rules allow it.'
                    ),
                    self::item(
                        'done',
                        'Appointment-assisted intake exists',
                        'Verification request creation can now import appointment context directly into the form in Clinic and Admin.'
                    ),
                ],
            ],
            [
                'title' => 'Risky',
                'tone' => 'danger',
                'items' => [
                    self::item(
                        config('app.debug') ? 'risk' : 'done',
                        'Debug mode',
                        config('app.debug')
                            ? 'Debug mode is currently enabled. This must be disabled before production launch.'
                            : 'Debug mode is disabled.'
                    ),
                    self::item(
                        config('app.env') === 'production' ? 'done' : 'risk',
                        'Environment mode',
                        config('app.env') === 'production'
                            ? 'Application is running in production mode.'
                            : 'Application is not in production mode. Final security validation still remains.'
                    ),
                    self::item(
                        config('session.driver') === 'database' ? 'done' : 'risk',
                        'Session persistence',
                        config('session.driver') === 'database'
                            ? 'Session driver is database-backed, which is good for central auditability and multi-user operations.'
                            : 'Session driver is not database-backed; review session handling before launch.'
                    ),
                    self::item(
                        is_link(public_path('storage')) ? 'done' : 'risk',
                        'Public storage link review',
                        is_link(public_path('storage'))
                            ? 'Public storage link exists. Uploaded/public assets must still be reviewed for PHI exposure.'
                            : 'Public storage link is not present. Storage and document delivery still need deployment review.'
                    ),
                    self::item(
                        'risk',
                        'Formal HIPAA compliance',
                        'Role separation and activity logs exist, but a formal HIPAA gap review, encryption/storage review, retention policy review, and infrastructure/vendor review are still required.'
                    ),
                ],
            ],
            [
                'title' => 'Pending',
                'tone' => 'warning',
                'items' => [
                    self::item(
                        'pending',
                        'Final PDF output refinement',
                        'PDF output options are in place, but the final market-ready layout still needs real clinic output review and print-quality sign-off.'
                    ),
                    self::item(
                        'pending',
                        'End-to-end workflow QA',
                        'Verification needs real-user walkthrough testing across single practice, DSO, and agency service models.'
                    ),
                    self::item(
                        'pending',
                        'Security and privacy checklist pass',
                        'Need a deliberate verification-focused checklist for PHI access, document storage, PDF distribution, logging, and operational procedures.'
                    ),
                    self::item(
                        'pending',
                        'Release readiness sign-off',
                        'The module still needs a final readiness sign-off once PDF output, workflow QA, and hardening are complete.'
                    ),
                ],
            ],
            [
                'title' => 'Nice to Have',
                'tone' => 'info',
                'items' => [
                    self::item(
                        'nice',
                        'Saved queue views per user',
                        'Helpful for operations, but not required to launch the eligibility verification module.'
                    ),
                    self::item(
                        'nice',
                        'Import history archive',
                        'Useful for audit and support, but the core import result summary already exists.'
                    ),
                    self::item(
                        'nice',
                        'Clinic-side verification dashboard metrics',
                        'Would improve adoption, but not necessary to deliver a production-ready verification workflow.'
                    ),
                ],
            ],
        ];
    }

    protected static function item(string $status, string $title, string $detail): array
    {
        return compact('status', 'title', 'detail');
    }
}
