<?php

namespace App\Filament\Clinic\Resources\PatientDocuments\Tables;

use App\Models\PatientDocument;
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

class PatientDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->state(fn (PatientDocument $record): string => $record->display_title)
                    ->searchable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PatientDocument $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('provider.display_name')
                    ->label('Provider')
                    ->state(fn (PatientDocument $record): ?string => $record->provider?->display_name)
                    ->toggleable(),
                TextColumn::make('file_size_label')
                    ->label('Size')
                    ->state(fn (PatientDocument $record): string => $record->file_size_label),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options(PatientDocument::TYPE_OPTIONS),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PatientDocument $record): string => route('clinic.patient-documents.show', $record))
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (PatientDocument $record): string => route('clinic.patient-documents.download', $record)),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicPatientDocuments() ?? false),
                DeleteAction::make()
                    ->visible(fn (PatientDocument $record): bool => (auth()->user()?->canDeleteClinicPatientDocuments() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientDocuments() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientDocuments() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientDocuments() ?? false),
                ]),
            ]);
    }
}
