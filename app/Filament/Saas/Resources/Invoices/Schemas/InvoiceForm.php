<?php

namespace App\Filament\Saas\Resources\Invoices\Schemas;

use App\Models\Invoice;
use App\Models\SaasSetting;
use App\Models\ServiceItem;
use App\Models\Subscription;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->label('Invoice number')
                    ->default(fn (): string => Invoice::generateInvoiceNumber())
                    ->required()
                    ->readOnly()
                    ->dehydrated()
                    ->maxLength(255),
                Select::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('subscription_id', null))
                    ->required(),
                Select::make('subscription_id')
                    ->label('Subscription')
                    ->options(fn (Get $get): array => filled($get('organization_id'))
                        ? Subscription::query()
                            ->where('organization_id', $get('organization_id'))
                            ->orderByDesc('start_date')
                            ->get()
                            ->mapWithKeys(fn (Subscription $subscription): array => [
                                $subscription->id => $subscription->subscriptionPlan?->name
                                    ? "{$subscription->subscriptionPlan->name} ({$subscription->status})"
                                    : "Subscription #{$subscription->id} ({$subscription->status})",
                            ])
                            ->all()
                        : [])
                    ->searchable()
                    ->preload(),
                DatePicker::make('issue_date')
                    ->default(now()->toDateString())
                    ->required(),
                DatePicker::make('due_date')
                    ->default(fn (): string => now()->addDays(max((int) (SaasSetting::current()->invoice_due_after_days ?? 3), 0))->toDateString()),
                Select::make('status')
                    ->default('draft')
                    ->required()
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                TextInput::make('amount_paid')
                    ->label('Amount paid')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->minValue(0)
                    ->step('0.01')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncBalance($get, $set)),
                TextInput::make('tax_amount')
                    ->label('Tax amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->minValue(0)
                    ->step('0.01')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncTotals($get, $set)),
                TextInput::make('discount_amount')
                    ->label('Discount amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->minValue(0)
                    ->step('0.01')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncTotals($get, $set)),
                Repeater::make('items')
                    ->relationship()
                    ->label('Invoice items')
                    ->defaultItems(1)
                    ->reorderable(false)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncTotals($get, $set))
                    ->schema([
                        Select::make('service_item_id')
                            ->label('Service item')
                            ->options(fn (): array => ServiceItem::query()
                                ->where('status', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Select a saved service')
                            ->helperText('Choose a saved service or leave blank for a custom line item.')
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if (blank($state)) {
                                    return;
                                }

                                $serviceItem = ServiceItem::query()->find($state);

                                if (! $serviceItem) {
                                    return;
                                }

                                $set('description', Str::limit($serviceItem->description ?: $serviceItem->name, 255, ''));
                                $set('unit_price', number_format((float) $serviceItem->default_price, 2, '.', ''));

                                static::syncLineItem($get, $set);
                            })
                            ->columnSpan(3),
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Manual service description')
                            ->columnSpan(3),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0.01)
                            ->step('0.01')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncLineItem($get, $set)),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->step('0.01')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncLineItem($get, $set)),
                        TextInput::make('line_total')
                            ->label('Line total')
                            ->prefix('$')
                            ->readOnly()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2, '.', '')),
                    ])
                    ->columns(9)
                    ->columnSpanFull(),
                Placeholder::make('subtotal_preview')
                    ->label('Subtotal')
                    ->content(fn (Get $get): string => '$' . number_format(static::calculateSubtotal($get('items')), 2)),
                Placeholder::make('total_preview')
                    ->label('Total')
                    ->content(fn (Get $get): string => '$' . number_format(static::calculateTotal($get), 2)),
                Placeholder::make('balance_preview')
                    ->label('Balance due')
                    ->content(fn (Get $get): string => '$' . number_format(static::calculateBalance($get), 2)),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function syncLineItem(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);

        $set('line_total', number_format($quantity * $unitPrice, 2, '.', ''));
    }

    protected static function syncTotals(Get $get, Set $set): void
    {
        $subtotal = static::calculateSubtotal($get('items'));
        $total = static::calculateTotal($get);

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('total_amount', number_format($total, 2, '.', ''));
        $set('balance_due', number_format(static::calculateBalance($get), 2, '.', ''));
    }

    protected static function syncBalance(Get $get, Set $set): void
    {
        $set('balance_due', number_format(static::calculateBalance($get), 2, '.', ''));
    }

    protected static function calculateSubtotal(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => (float) ($item['line_total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0))));
    }

    protected static function calculateTotal(Get $get): float
    {
        $subtotal = static::calculateSubtotal($get('items'));
        $tax = (float) ($get('tax_amount') ?? 0);
        $discount = (float) ($get('discount_amount') ?? 0);

        return max($subtotal + $tax - $discount, 0);
    }

    protected static function calculateBalance(Get $get): float
    {
        $total = static::calculateTotal($get);
        $amountPaid = (float) ($get('amount_paid') ?? 0);

        return max($total - $amountPaid, 0);
    }
}
