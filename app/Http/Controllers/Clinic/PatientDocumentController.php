<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientDocumentController extends Controller
{
    public function show(Request $request, PatientDocument $document)
    {
        $this->authorizeAccess($document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->response(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($document->original_name) . '"',
            ],
        );
    }

    public function download(Request $request, PatientDocument $document)
    {
        $this->authorizeAccess($document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            ],
        );
    }

    protected function authorizeAccess(PatientDocument $document): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->canAccessClinicPatientDocuments()
            && $user->organization_id === $document->organization_id
            && $user->clinic_id === $document->clinic_id,
            403,
        );
    }
}
