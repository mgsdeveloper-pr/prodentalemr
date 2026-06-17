<?php

namespace App\Filament\Clinic\Resources\Appointments;

use App\Filament\Clinic\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Clinic\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Clinic\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Clinic\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Clinic\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Clinic\Resources\Appointments\Schemas\AppointmentInfolist;
use App\Filament\Clinic\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Appointments';

    protected static string|UnitEnum|null $navigationGroup = 'Scheduling';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'appointment_type';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return ClinicWorkspace::selected() === ClinicWorkspace::VERIFICATION
            ? 'Verifications'
            : 'Scheduling';
    }

    public static function getNavigationLabel(): string
    {
        return ClinicWorkspace::selected() === ClinicWorkspace::VERIFICATION
            ? 'Appointment'
            : 'Appointments';
    }

    public static function getNavigationSort(): ?int
    {
        return ClinicWorkspace::selected() === ClinicWorkspace::VERIFICATION ? 2 : 2;
    }

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
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
            ->with(['patient', 'provider.user', 'location', 'operatory']);

        $selectedClinicId = ClinicPanelScope::selectedClinicId();
        $selectedOrganizationId = ClinicPanelScope::selectedOrganizationId();

        if (! $selectedOrganizationId || ! $selectedClinicId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $selectedOrganizationId)
            ->where('clinic_id', $selectedClinicId);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicAppointments() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicAppointments() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicAppointments() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicAppointments() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
