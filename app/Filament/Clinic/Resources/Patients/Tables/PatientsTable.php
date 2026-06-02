<?php

namespace App\Filament\Clinic\Resources\Patients\Tables;

use App\Models\Patient;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Patient')
                    ->state(fn (Patient $record): string => $record->full_name)
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($innerQuery) use ($search): void {
                            $innerQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('last_name', $direction)
                        ->orderBy('first_name', $direction)),
                TextColumn::make('dob')
                    ->label('DOB')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('age_label')
                    ->label('Age')
                    ->state(fn (Patient $record): ?string => $record->age_label)
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('insurance_provider')
                    ->label('Insurance')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('appointments_count')
                    ->label('Visits')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'non_binary' => 'Non-binary',
                        'prefer_not_to_say' => 'Prefer not to say',
                    ]),
                TernaryFilter::make('status')
                    ->label('Active status'),
                TrashedFilter::make(),
            ])
            ->defaultSort('last_name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicPatients() ?? false),
                DeleteAction::make()
                    ->visible(fn (Patient $record): bool => (auth()->user()?->canDeleteClinicPatients() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatients() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatients() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatients() ?? false),
                ]),
            ]);
    }
}
