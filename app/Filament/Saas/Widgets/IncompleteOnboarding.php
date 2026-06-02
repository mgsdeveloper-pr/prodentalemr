<?php

namespace App\Filament\Saas\Widgets;

use App\Models\OnboardingDraft;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class IncompleteOnboarding extends TableWidget
{
    protected static ?string $heading = 'Incomplete Onboarding';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OnboardingDraft::query()
                    ->with('user')
                    ->where('type', 'organization_onboarding')
                    ->select('onboarding_drafts.*')
                    ->selectRaw("COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.organization_name')), ''), 'Untitled organization') as organization_name")
                    ->latest('updated_at')
                    ->limit(6)
            )
            ->columns([
                TextColumn::make('organization_name')
                    ->label('Organization')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Started By')
                    ->searchable()
                    ->default('Unknown'),
                TextColumn::make('last_completed_step')
                    ->label('Last Step')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ((int) $state) {
                        4 => 'Owner Account',
                        3 => 'Location',
                        2 => 'Clinic',
                        default => 'Organization',
                    })
                    ->color('warning'),
                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->defaultKeySort(false)
            ->paginated(false)
            ->emptyStateHeading('No incomplete onboarding drafts')
            ->emptyStateDescription('Organizations that stop midway through onboarding will appear here.');
    }
}
