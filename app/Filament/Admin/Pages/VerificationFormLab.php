<?php

namespace App\Filament\Admin\Pages;

use App\Support\AdminClinicScope;
use App\Support\SaasEntitlements;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class VerificationFormLab extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?string $navigationLabel = 'Form Lab';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = '';

    protected static ?string $slug = 'verification-form-lab';

    protected string $view = 'filament.admin.pages.verification-form-lab';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canAccessVerificationWorkspace() ?? false)
            && SaasEntitlements::userFeatureAllowed($user, 'verification_requests', AdminClinicScope::selectedClinic());
    }
}
