<?php

namespace App\Filament\Saas\Widgets;

use App\Models\User;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Notifications\DatabaseNotification;

class SaasNotificationsOverview extends TableWidget
{
    protected static ?string $heading = 'Notifications';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', auth()->id())
                    ->select('notifications.*')
                    ->selectRaw("COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.title')), ''), 'Notification') as notification_title")
                    ->selectRaw("COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.body')), ''), 'No details available.') as notification_body")
                    ->latest('created_at')
                    ->limit(6)
            )
            ->columns([
                TextColumn::make('notification_title')
                    ->label('Alert')
                    ->weight(FontWeight::Medium)
                    ->wrap(),
                TextColumn::make('notification_body')
                    ->label('Details')
                    ->limit(70)
                    ->wrap(),
                IconColumn::make('read_at')
                    ->label('Read')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-bell-alert'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultKeySort(false)
            ->paginated(false)
            ->emptyStateHeading('No notifications yet')
            ->emptyStateDescription('Platform alerts and system notifications will appear here.');
    }
}
