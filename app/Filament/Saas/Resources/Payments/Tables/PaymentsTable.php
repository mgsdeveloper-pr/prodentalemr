<?php

namespace App\Filament\Saas\Resources\Payments\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use App\Models\Payment;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->sortable(),
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label('Recorded by')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->options(Payment::methodOptions())
                    ->label('Method'),
                Filter::make('payment_date_range')
                    ->schema([
                        DatePicker::make('paid_from')
                            ->label('Paid from'),
                        DatePicker::make('paid_until')
                            ->label('Paid until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['paid_from'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '>=', $date))
                            ->when($data['paid_until'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('viewInvoice')
                        ->label('View Invoice')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->url(fn (Payment $record): string => \App\Filament\Saas\Resources\Invoices\InvoiceResource::getUrl('view', ['record' => $record->invoice])),
                    DeleteAction::make()
                        ->visible(fn (Payment $record): bool => ! $record->trashed()),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->button(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
