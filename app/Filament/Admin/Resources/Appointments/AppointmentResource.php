<?php

namespace App\Filament\Admin\Resources\Appointments;

use App\Filament\Admin\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Admin\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Admin\Resources\Appointments\Pages\ImportAppointments;
use App\Filament\Admin\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Admin\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Admin\Resources\Appointments\Tables\AppointmentsTable;
use App\Filament\Clinic\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Clinic\Resources\Appointments\Schemas\AppointmentInfolist;
use App\Models\Appointment;
use App\Support\AdminClinicScope;
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

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'appointment_type';

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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['patient', 'provider.user', 'location', 'operatory']);

        $selectedClinicId = AdminClinicScope::selectedClinicId();

        if ($selectedClinicId) {
            return AdminClinicScope::apply($query);
        }

        $clinicIds = AdminClinicScope::clinics()->pluck('id')->all();

        if ($clinicIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('clinic_id', $clinicIds);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessVerificationWorkspace() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess() && filled(AdminClinicScope::selectedClinicId());
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function canDelete($record): bool
    {
        return static::canAccess();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'import' => ImportAppointments::route('/import'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
