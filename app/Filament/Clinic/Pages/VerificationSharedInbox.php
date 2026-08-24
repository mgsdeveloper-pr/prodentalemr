<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Admin\Pages\VerificationInbox;
use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\Clinic;
use App\Models\VerificationInboxAttachment;
use App\Models\VerificationInboxMessage;
use App\Support\ClinicPanelScope;
use App\Support\SaasEntitlements;
use App\Support\VerificationManagedServiceAccess;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VerificationSharedInbox extends VerificationInbox
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Verification';

    protected static ?string $navigationLabel = 'Shared Inbox';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Shared Inbox';

    protected static ?string $slug = 'shared-inbox';

    public static function canAccess(): bool
    {
        return VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()
            && (auth()->user()?->canAccessClinicVerificationRequests() ?? false)
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'clinic_inbox', ClinicPanelScope::selectedClinic());
    }

    public function getBreadcrumbs(): array
    {
        return [
            VerificationRequestResource::getUrl('index') => 'Verification',
            'Shared Inbox',
        ];
    }

    public function attachmentDownloadUrl(VerificationInboxAttachment $attachment): string
    {
        return route('clinic.verification-inbox-attachments.download', $attachment);
    }

    public function messagePreviewUrl(VerificationInboxMessage $message): string
    {
        return route('clinic.verification-inbox-messages.preview', $message);
    }

    protected function selectedClinic(): ?Clinic
    {
        return ClinicPanelScope::selectedClinic();
    }

    protected function selectedClinicId(): ?int
    {
        return ClinicPanelScope::selectedClinicId();
    }

    protected function applyClinicScope(Builder $query): Builder
    {
        return ClinicPanelScope::apply($query, 'clinic_id');
    }
}
