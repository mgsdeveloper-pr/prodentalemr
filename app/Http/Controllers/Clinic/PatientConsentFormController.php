<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\PatientConsentForm;
use Illuminate\Support\Facades\Storage;

class PatientConsentFormController extends Controller
{
    public function show(PatientConsentForm $consent)
    {
        $this->authorizeAccess($consent);

        abort_unless(filled($consent->file_path), 404);
        abort_unless(Storage::disk('local')->exists($consent->file_path), 404);

        return Storage::disk('local')->response(
            $consent->file_path,
            $consent->original_filename,
            [
                'Content-Type' => $consent->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($consent->original_filename ?: basename($consent->file_path)) . '"',
            ],
        );
    }

    public function download(PatientConsentForm $consent)
    {
        $this->authorizeAccess($consent);

        abort_unless(filled($consent->file_path), 404);
        abort_unless(Storage::disk('local')->exists($consent->file_path), 404);

        return Storage::disk('local')->download(
            $consent->file_path,
            $consent->original_filename ?: basename($consent->file_path),
            [
                'Content-Type' => $consent->mime_type ?: 'application/octet-stream',
            ],
        );
    }

    protected function authorizeAccess(PatientConsentForm $consent): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->canAccessClinicConsentForms()
            && (int) $user->organization_id === (int) $consent->organization_id
            && (int) $user->clinic_id === (int) $consent->clinic_id,
            403,
        );
    }
}
