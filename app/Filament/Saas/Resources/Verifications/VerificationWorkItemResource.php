<?php

namespace App\Filament\Saas\Resources\Verifications;

use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\ActivitiesRelationManager;
use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\AttachmentsRelationManager;
use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\NotesRelationManager;
use App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationWorkItem;
use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationWorkItem;
use App\Filament\Saas\Resources\Verifications\Pages\ImportVerificationWorkItems;
use App\Filament\Saas\Resources\Verifications\Pages\ListVerificationWorkItems;
use App\Filament\Saas\Resources\Verifications\Pages\ViewVerificationWorkItem;
use App\Filament\Saas\Resources\Verifications\Schemas\VerificationWorkItemForm;
use App\Filament\Saas\Resources\Verifications\Schemas\VerificationWorkItemInfolist;
use App\Filament\Saas\Resources\Verifications\Tables\VerificationWorkItemsTable;
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

class VerificationWorkItemResource extends Resource
{
    protected static ?string $model = BillingWorkItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Verification Queue';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

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
        return VerificationWorkItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VerificationWorkItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VerificationWorkItemsTable::configure($table);
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
        $query = AdminClinicScope::apply(parent::getEloquentQuery(), 'clinic_id')
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))
            ->where('source', '!=', 'clinic_self_service')
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
                'notes.user',
                'attachments',
                'activities.user',
            ]);

        $user = auth()->user();

        if ($user?->hasRole('verification_user') && ! $user->canManageVerificationQueue()) {
            $query->where('assigned_to', $user->getAuthIdentifier());
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessVerificationModule('verification') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformVerificationModuleAction('verification', 'add') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canPerformVerificationModuleAction('verification', 'update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canPerformVerificationModuleAction('verification', 'delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationWorkItems::route('/'),
            'create' => CreateVerificationWorkItem::route('/create'),
            'import' => ImportVerificationWorkItems::route('/import'),
            'view' => ViewVerificationWorkItem::route('/{record}'),
            'edit' => EditVerificationWorkItem::route('/{record}/edit'),
        ];
    }
}
