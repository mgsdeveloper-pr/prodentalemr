<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans;

use App\Filament\Clinic\Resources\TreatmentPlans\Pages\CreateTreatmentPlan;
use App\Filament\Clinic\Resources\TreatmentPlans\Pages\EditTreatmentPlan;
use App\Filament\Clinic\Resources\TreatmentPlans\Pages\ListTreatmentPlans;
use App\Filament\Clinic\Resources\TreatmentPlans\Pages\ViewTreatmentPlan;
use App\Filament\Clinic\Resources\TreatmentPlans\Schemas\TreatmentPlanForm;
use App\Filament\Clinic\Resources\TreatmentPlans\Schemas\TreatmentPlanInfolist;
use App\Filament\Clinic\Resources\TreatmentPlans\Tables\TreatmentPlansTable;
use App\Models\TreatmentPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TreatmentPlanResource extends Resource
{
    protected static ?string $model = TreatmentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Treatment Plans';

    protected static string|UnitEnum|null $navigationGroup = 'Treatment Planning';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return TreatmentPlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TreatmentPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreatmentPlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['patient', 'provider.user', 'location', 'appointment', 'encounter', 'creator'])
            ->withCount('items');

        $user = auth()->user();

        if (! $user?->organization_id || ! $user?->clinic_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicTreatmentPlans() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicTreatmentPlans() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicTreatmentPlans() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicTreatmentPlans() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTreatmentPlans::route('/'),
            'create' => CreateTreatmentPlan::route('/create'),
            'view' => ViewTreatmentPlan::route('/{record}'),
            'edit' => EditTreatmentPlan::route('/{record}/edit'),
        ];
    }
}
