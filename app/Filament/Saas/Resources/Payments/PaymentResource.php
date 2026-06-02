<?php

namespace App\Filament\Saas\Resources\Payments;

use App\Filament\Saas\Resources\Payments\Pages\CreatePayment;
use App\Filament\Saas\Resources\Payments\Pages\EditPayment;
use App\Filament\Saas\Resources\Payments\Pages\ListPayments;
use App\Filament\Saas\Resources\Payments\Pages\ViewPayment;
use App\Filament\Saas\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Saas\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Saas\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Payments';

    protected static string|UnitEnum|null $navigationGroup = 'Payment Systems';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('payments') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('payments', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('payments', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('payments', 'delete') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['invoice', 'organization', 'creator']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
