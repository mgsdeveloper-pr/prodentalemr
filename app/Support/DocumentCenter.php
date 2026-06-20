<?php

namespace App\Support;

use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Models\PatientDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentCenter
{
    public static function rows(string $panel, ?string $search = null, string $type = 'all', string $dateRange = 'all'): Collection
    {
        $rows = collect();

        if (in_array($type, ['all', 'verification'], true)) {
            $rows = $rows->merge(self::verificationAttachmentRows($panel, $search, $dateRange));
        }

        if ($panel === 'clinic' && in_array($type, ['all', 'patient'], true)) {
            $rows = $rows->merge(self::patientDocumentRows($search, $dateRange));
        }

        return $rows
            ->sortByDesc('uploaded_at_sort')
            ->values();
    }

    public static function stats(string $panel): array
    {
        $rows = self::rows($panel);

        return [
            'total' => $rows->count(),
            'verification' => $rows->where('type_key', 'verification')->count(),
            'patient' => $rows->where('type_key', 'patient')->count(),
            'storage' => self::formatBytes($rows->sum('size_bytes')),
        ];
    }

    protected static function verificationAttachmentRows(string $panel, ?string $search, string $dateRange): Collection
    {
        $query = BillingWorkItemAttachment::query()
            ->with(['workItem.patient', 'workItem.appointment.patient', 'workItem.clinic', 'user'])
            ->whereHas('workItem', function (Builder $query) use ($panel): void {
                $query->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'));

                if ($panel === 'admin') {
                    AdminClinicScope::apply($query, 'clinic_id');
                } else {
                    ClinicPanelScope::apply($query, 'clinic_id');
                }
            });

        self::applyDateRange($query, $dateRange);

        if (filled($search)) {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('original_file_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('workItem.patient', function (Builder $patientQuery) use ($search): void {
                        $patientQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('workItem', fn (Builder $workItemQuery) => $workItemQuery->where('reference_number', 'like', "%{$search}%"));
            });
        }

        return $query
            ->latest()
            ->limit(250)
            ->get()
            ->map(function (BillingWorkItemAttachment $attachment) use ($panel): array {
                $workItem = $attachment->workItem;
                $downloadRoute = $panel === 'admin'
                    ? 'saas.billing-work-item-attachments.download'
                    : 'clinic.billing-work-item-attachments.download';
                $previewRoute = $panel === 'admin'
                    ? 'saas.billing-work-item-attachments.preview'
                    : 'clinic.billing-work-item-attachments.preview';

                return [
                    'id' => 'verification-' . $attachment->id,
                    'type_key' => 'verification',
                    'type_label' => self::verificationAttachmentLabel($attachment),
                    'title' => $attachment->title ?: ($attachment->original_file_name ?: 'Verification document'),
                    'file_name' => $attachment->original_file_name ?: basename((string) $attachment->file_path),
                    'patient' => self::workItemPatientName($workItem),
                    'clinic' => $workItem?->clinic?->clinic_name ?: '-',
                    'source' => $workItem?->reference_number ?: 'Verification',
                    'uploaded_by' => $attachment->user?->name ?: 'System',
                    'uploaded_at' => $attachment->created_at?->format('M d, Y h:i A') ?: '-',
                    'uploaded_at_sort' => $attachment->created_at?->timestamp ?? 0,
                    'size_label' => self::formatBytes((int) $attachment->file_size),
                    'size_bytes' => (int) $attachment->file_size,
                    'preview_url' => Route::has($previewRoute) ? route($previewRoute, $attachment) : null,
                    'download_url' => Route::has($downloadRoute) ? route($downloadRoute, $attachment) : null,
                    'related_url' => $panel === 'admin' && $workItem
                        ? VerificationWorkItemResource::getUrl('edit', ['record' => $workItem])
                        : url('/clinic/request-response'),
                    'is_available' => filled($attachment->file_path) && Storage::disk('local')->exists($attachment->file_path),
                ];
            });
    }

    protected static function patientDocumentRows(?string $search, string $dateRange): Collection
    {
        $query = PatientDocument::query()
            ->with(['patient', 'clinic', 'uploader']);

        ClinicPanelScope::apply($query, 'clinic_id');
        self::applyDateRange($query, $dateRange);

        if (filled($search)) {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhereHas('patient', function (Builder $patientQuery) use ($search): void {
                        $patientQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->latest()
            ->limit(250)
            ->get()
            ->map(fn (PatientDocument $document): array => [
                'id' => 'patient-' . $document->id,
                'type_key' => 'patient',
                'type_label' => PatientDocument::TYPE_OPTIONS[$document->document_type] ?? Str::headline((string) $document->document_type),
                'title' => $document->display_title,
                'file_name' => $document->original_name ?: basename((string) $document->path),
                'patient' => $document->patient?->full_name ?: '-',
                'clinic' => $document->clinic?->clinic_name ?: '-',
                'source' => 'Patient Document',
                'uploaded_by' => $document->uploader?->name ?: 'System',
                'uploaded_at' => $document->created_at?->format('M d, Y h:i A') ?: '-',
                'uploaded_at_sort' => $document->created_at?->timestamp ?? 0,
                'size_label' => $document->file_size_label,
                'size_bytes' => (int) $document->file_size,
                'preview_url' => Route::has('clinic.patient-documents.show') ? route('clinic.patient-documents.show', $document) : null,
                'download_url' => Route::has('clinic.patient-documents.download') ? route('clinic.patient-documents.download', $document) : null,
                'related_url' => null,
                'is_available' => filled($document->path) && Storage::disk($document->disk ?: 'local')->exists($document->path),
            ]);
    }

    protected static function applyDateRange(Builder $query, string $dateRange): void
    {
        match ($dateRange) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            default => null,
        };
    }

    protected static function verificationAttachmentLabel(BillingWorkItemAttachment $attachment): string
    {
        $title = strtolower((string) $attachment->title);
        $path = strtolower((string) $attachment->file_path);

        if (str_contains($title, 'clinic response') || str_contains($path, 'clinic-response')) {
            return 'Clinic Response';
        }

        return 'Verification Attachment';
    }

    protected static function workItemPatientName(?BillingWorkItem $workItem): string
    {
        if (! $workItem) {
            return '-';
        }

        $patientName = $workItem->patient?->full_name
            ?: $workItem->appointment?->patient?->full_name;

        if (filled($patientName)) {
            return $patientName;
        }

        $title = trim((string) $workItem->title);

        if (preg_match('/^Insurance Verification\s*-\s*(.*?)\s*-\s*[A-Z][a-z]{2,9}\s+\d{1,2},\s+\d{4}$/', $title, $matches)) {
            return trim($matches[1]) ?: '-';
        }

        if (str_contains($title, ' - ')) {
            $parts = array_values(array_filter(array_map('trim', explode(' - ', $title))));

            if (count($parts) >= 2) {
                return $parts[1];
            }
        }

        return filled($title) ? $title : '-';
    }

    protected static function formatBytes(int $bytes): string
    {
        return match (true) {
            $bytes >= 1073741824 => number_format($bytes / 1073741824, 2) . ' GB',
            $bytes >= 1048576 => number_format($bytes / 1048576, 2) . ' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 2) . ' KB',
            default => $bytes . ' B',
        };
    }
}
