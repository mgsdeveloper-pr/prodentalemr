<?php

namespace App\Filament\Clinic\Resources\VerificationRequests;

use App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest;
use App\Filament\Clinic\Resources\VerificationRequests\Pages\EditVerificationRequest;
use App\Filament\Clinic\Resources\VerificationRequests\Pages\ImportVerificationRequests;
use App\Filament\Clinic\Resources\VerificationRequests\Pages\ListVerificationRequests;
use App\Filament\Clinic\Resources\VerificationRequests\Pages\ViewVerificationRequest;
use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestInfolist;
use App\Filament\Clinic\Resources\VerificationRequests\Tables\VerificationRequestsTable;
use App\Models\BillingWorkItem;
use App\Support\ClinicPanelScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VerificationRequestResource extends Resource
{
    protected static ?string $model = BillingWorkItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Insurance Verification';

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?string $slug = 'verification-requests';

    public static function getModelLabel(): string
    {
        return 'insurance verification';
    }

    public static function getPluralModelLabel(): string
    {
        return 'insurance verification';
    }

    public static function getNavigationLabel(): string
    {
        return 'Verification List';
    }

    public static function form(Schema $schema): Schema
    {
        return VerificationRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VerificationRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VerificationRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
            ->with([
                'managedBillingService',
                'enrollment',
                'organization',
                'clinic',
                'location',
                'patient',
                'provider.user',
                'appointment',
                'insurancePolicy',
                'assignedTo',
                'reviewedBy',
            ]);

        if ($user?->shouldBypassClinicScope()) {
            return ClinicPanelScope::apply($query);
        }

        if (! $user?->organization_id || ! $user?->clinic_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicVerificationRequests() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicVerificationRequests() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return $record->clinicUserCanEditVerification(auth()->user());
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationRequests::route('/'),
            'create' => CreateVerificationRequest::route('/create'),
            'import' => ImportVerificationRequests::route('/import'),
            'view' => ViewVerificationRequest::route('/{record}'),
            'edit' => EditVerificationRequest::route('/{record}/edit'),
        ];
    }
}
