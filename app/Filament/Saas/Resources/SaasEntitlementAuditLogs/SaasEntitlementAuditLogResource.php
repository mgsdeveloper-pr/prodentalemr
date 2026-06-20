<?php

namespace App\Filament\Saas\Resources\SaasEntitlementAuditLogs;

use App\Filament\Saas\Resources\SaasEntitlementAuditLogs\Pages\ListSaasEntitlementAuditLogs;
use App\Filament\Saas\Resources\SaasEntitlementAuditLogs\Tables\SaasEntitlementAuditLogsTable;
use App\Models\SaasEntitlementAuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SaasEntitlementAuditLogResource extends Resource
{
    protected static ?string $model = SaasEntitlementAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Entitlement Audit';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    public static function table(Table $table): Table
    {
        return SaasEntitlementAuditLogsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('settings') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaasEntitlementAuditLogs::route('/'),
        ];
    }
}
