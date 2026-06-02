<?php

namespace App\Filament\Saas\Resources\Invoices;

use App\Filament\Saas\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Saas\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Saas\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Saas\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Saas\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Saas\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Filament\Saas\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Invoices';

    protected static string|UnitEnum|null $navigationGroup = 'Payment Systems';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('invoices') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('invoices', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('invoices', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('invoices', 'delete') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['organization', 'subscription.subscriptionPlan', 'payments.creator']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
