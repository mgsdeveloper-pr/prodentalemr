<?php

namespace App\Filament\Saas\Resources\Verifications;

use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\ActivitiesRelationManager;
use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\AttachmentsRelationManager;
use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\NotesRelationManager;
use App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest;
use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest;
use App\Filament\Saas\Resources\Verifications\Pages\ImportVerificationRequests;
use App\Filament\Saas\Resources\Verifications\Pages\ListVerificationRequests;
use App\Filament\Saas\Resources\Verifications\Pages\ViewVerificationRequest;
use App\Filament\Saas\Resources\Verifications\Schemas\VerificationRequestQueueForm;
use App\Filament\Saas\Resources\Verifications\Schemas\VerificationRequestInfolist;
use App\Filament\Saas\Resources\Verifications\Tables\VerificationRequestsTable;
use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VerificationRequestResource extends Resource
{
    protected static ?string $model = BillingWorkItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Verification List';

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?string $slug = 'verifications';

    public static function getModelLabel(): string
    {
        return 'verification request';
    }

    public static function getPluralModelLabel(): string
    {
        return 'verification requests';
    }

    public static function form(Schema $schema): Schema
    {
        return VerificationRequestQueueForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VerificationRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VerificationRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
            AttachmentsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = AdminClinicScope::applyVerificationRequests(parent::getEloquentQuery(), 'clinic_id')
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))
            ->where('processing_mode', BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE)
            ->with([
                'managedBillingService',
                'organization',
                'clinic',
                'location',
                'patient',
                'provider.user',
                'insurancePolicy',
                'insuranceClaim',
                'appointment',
                'assignedTo',
                'reviewedBy',
                'verificationProfile',
                'verificationPlanSnapshots',
                'workNotes.user',
                'attachments',
                'activities.user',
            ]);

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', BillingWorkItem::class) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view', $record) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', BillingWorkItem::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->normalized_status !== BillingWorkItem::STATUS_DONE
            && (auth()->user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
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
