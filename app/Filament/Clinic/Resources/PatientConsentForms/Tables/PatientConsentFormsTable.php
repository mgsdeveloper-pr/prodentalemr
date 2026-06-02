<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms\Tables;

use App\Models\PatientConsentForm;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PatientConsentFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PatientConsentForm $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('form_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('signed_on')
                    ->date('M d, Y')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('form_type')
                    ->options(PatientConsentForm::FORM_TYPE_OPTIONS),
                SelectFilter::make('status')
                    ->options(PatientConsentForm::STATUS_OPTIONS),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('document_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PatientConsentForm $record): string => route('clinic.patient-consent-forms.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (PatientConsentForm $record): bool => filled($record->file_path)),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (PatientConsentForm $record): string => route('clinic.patient-consent-forms.download', $record))
                    ->visible(fn (PatientConsentForm $record): bool => filled($record->file_path)),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicConsentForms() ?? false),
                DeleteAction::make()
                    ->visible(fn (PatientConsentForm $record): bool => (auth()->user()?->canDeleteClinicConsentForms() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicConsentForms() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicConsentForms() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicConsentForms() ?? false),
                ]),
            ]);
    }
}
