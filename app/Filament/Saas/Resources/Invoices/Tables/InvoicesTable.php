<?php

namespace App\Filament\Saas\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\Action;
use App\Filament\Saas\Resources\Invoices\Support\InvoiceRecordActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscription.subscriptionPlan.name')
                    ->label('Plan')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                Filter::make('issue_date_range')
                    ->schema([
                        DatePicker::make('issued_from')
                            ->label('Issued from'),
                        DatePicker::make('issued_until')
                            ->label('Issued until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['issued_from'] ?? null, fn ($query, $date) => $query->whereDate('issue_date', '>=', $date))
                            ->when($data['issued_until'] ?? null, fn ($query, $date) => $query->whereDate('issue_date', '<=', $date));
                    }),
                Filter::make('due_date_range')
                    ->schema([
                        DatePicker::make('due_from')
                            ->label('Due from'),
                        DatePicker::make('due_until')
                            ->label('Due until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['due_from'] ?? null, fn ($query, $date) => $query->whereDate('due_date', '>=', $date))
                            ->when($data['due_until'] ?? null, fn ($query, $date) => $query->whereDate('due_date', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    InvoiceRecordActions::downloadPdf(),
                    InvoiceRecordActions::viewPdf(),
                    InvoiceRecordActions::send(),
                    InvoiceRecordActions::copyPaymentLink(),
                    InvoiceRecordActions::paymentPage(),
                    InvoiceRecordActions::copyPayPalLink(),
                    InvoiceRecordActions::payPalPage(),
                    Action::make('editInvoice')
                        ->label('Edit')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->url(fn (Invoice $record): string => InvoiceRecordActions::editUrl($record)),
                    InvoiceRecordActions::addPayment(),
                    InvoiceRecordActions::paymentReminder(),
                    InvoiceRecordActions::cancel(),
                    InvoiceRecordActions::duplicate(),
                    InvoiceRecordActions::softDelete(),
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
