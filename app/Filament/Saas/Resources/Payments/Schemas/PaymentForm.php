<?php

namespace App\Filament\Saas\Resources\Payments\Schemas;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->relationship('invoice', 'invoice_number', fn ($query) => $query->with('organization'))
                                    ->getOptionLabelFromRecordUsing(fn (Invoice $record): string => $record->invoice_number . ' - ' . ($record->organization?->name ?? 'Organization'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Placeholder::make('organization_name')
                                    ->label('Organization')
                                    ->content(function (Get $get): string {
                                        $invoiceId = $get('invoice_id');

                                        if (blank($invoiceId)) {
                                            return '-';
                                        }

                                        return Invoice::query()->with('organization')->find($invoiceId)?->organization?->name ?? '-';
                                    }),
                                DatePicker::make('payment_date')
                                    ->label('Payment date')
                                    ->default(now()->toDateString())
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0.01)
                                    ->step('0.01'),
                                Select::make('payment_method')
                                    ->label('Payment method')
                                    ->options(Payment::methodOptions())
                                    ->required()
                                    ->default('manual')
                                    ->native(false),
                                TextInput::make('reference_number')
                                    ->label('Reference number')
                                    ->maxLength(255),
                            ]),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
