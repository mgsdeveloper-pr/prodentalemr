<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments;

use App\Filament\Saas\Resources\ClientServiceEnrollments\Pages\CreateClientServiceEnrollment;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Pages\EditClientServiceEnrollment;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Pages\ListClientServiceEnrollments;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Pages\ViewClientServiceEnrollment;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Schemas\ClientServiceEnrollmentForm;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Schemas\ClientServiceEnrollmentInfolist;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Tables\ClientServiceEnrollmentsTable;
use App\Models\ClientServiceEnrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ClientServiceEnrollmentResource extends Resource
{
    protected static ?string $model = ClientServiceEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Client Enrollments';

    protected static string|UnitEnum|null $navigationGroup = 'Plans';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return ClientServiceEnrollmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientServiceEnrollmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientServiceEnrollmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['organization', 'clinic', 'location', 'managedBillingService', 'creator'])
            ->withCount('workItems');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('client_enrollments') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('client_enrollments', 'add') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('client_enrollments', 'update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('client_enrollments', 'delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientServiceEnrollments::route('/'),
            'create' => CreateClientServiceEnrollment::route('/create'),
            'view' => ViewClientServiceEnrollment::route('/{record}'),
            'edit' => EditClientServiceEnrollment::route('/{record}/edit'),
        ];
    }
}
