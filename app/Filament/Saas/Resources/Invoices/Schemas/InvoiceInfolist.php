<?php

namespace App\Filament\Saas\Resources\Invoices\Schemas;

use App\Models\SaasSetting;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Overview')
                    ->description('Billing owner, invoice lifecycle, and collection status for this record.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('Invoice number')
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state): string => match ($state) {
                                        'paid' => 'success',
                                        'partial' => 'warning',
                                        'overdue' => 'danger',
                                        'cancelled' => 'gray',
                                        'sent' => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('organization.name')
                                    ->label('Organization')
                                    ->columnSpan(2),
                                TextEntry::make('subscription.subscriptionPlan.name')
                                    ->label('Subscription plan')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('issue_date')
                                    ->date(),
                                TextEntry::make('due_date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('paid_at')
                                    ->date()
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Financial Snapshot')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->money('USD')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('tax_amount')
                                    ->money('USD')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('discount_amount')
                                    ->money('USD')
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('total_amount')
                                    ->money('USD')
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('amount_paid')
                                    ->money('USD')
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('balance_due')
                                    ->money('USD')
                                    ->badge()
                                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),
                            ]),
                    ]),
                Section::make('ACH / Wire Payment Details')
                    ->visible(function (): bool {
                        $settings = SaasSetting::current();

                        return filled($settings->bank_account_name)
                            || filled($settings->bank_account_type)
                            || filled($settings->bank_name)
                            || filled($settings->bank_account_number)
                            || filled($settings->bank_routing_number)
                            || filled($settings->bank_swift_code)
                            || filled($settings->bank_branch)
                            || filled($settings->bank_payment_notes);
                    })
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('bank_account_name')
                                    ->label('Account holder')
                                    ->state(fn (): ?string => SaasSetting::current()->bank_account_name)
                                    ->placeholder('-'),
                                TextEntry::make('bank_account_type')
                                    ->label('Account type')
                                    ->state(fn (): ?string => filled(SaasSetting::current()->bank_account_type) ? ucfirst((string) SaasSetting::current()->bank_account_type) : null)
                                    ->placeholder('-'),
                                TextEntry::make('bank_name')
                                    ->label('Bank name')
                                    ->state(fn (): ?string => SaasSetting::current()->bank_name)
                                    ->placeholder('-'),
                                TextEntry::make('bank_account_number')
                                    ->label('Account number')
                                    ->state(fn (): ?string => SaasSetting::current()->bank_account_number)
                                    ->placeholder('-'),
                                TextEntry::make('bank_routing_number')
                                    ->label('ABA routing number')
                                    ->state(fn (): ?string => SaasSetting::current()->bank_routing_number)
                                    ->placeholder('-'),
                                TextEntry::make('bank_swift_code')
                                    ->label('SWIFT / BIC')
                                    ->state(fn (): ?string => SaasSetting::current()->bank_swift_code)
                                    ->placeholder('-'),
                                TextEntry::make('bank_branch')
                                    ->label('Bank branch / address')
                                    ->state(fn (): ?string => SaasSetting::current()->bank_branch)
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('bank_payment_notes')
                                    ->label('Payment notes')
                                    ->state(fn (): ?string => SaasSetting::current()->bank_payment_notes)
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Payment Gateway References')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('stripe_checkout_session_id')
                                    ->label('Stripe session')
                                    ->placeholder('-'),
                                TextEntry::make('stripe_payment_intent_id')
                                    ->label('Stripe payment intent')
                                    ->placeholder('-'),
                                TextEntry::make('paypal_order_id')
                                    ->label('PayPal order')
                                    ->placeholder('-'),
                                TextEntry::make('paypal_capture_id')
                                    ->label('PayPal capture')
                                    ->placeholder('-'),
                                TextEntry::make('paypal_order_status')
                                    ->label('PayPal status')
                                    ->badge()
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Invoice Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('description')
                                    ->columnSpan(2),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price')
                                    ->money('USD'),
                                TextEntry::make('line_total')
                                    ->money('USD'),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('Payments Applied')
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->label('')
                            ->schema([
                                TextEntry::make('payment_date')
                                    ->date(),
                                TextEntry::make('amount')
                                    ->money('USD'),
                                TextEntry::make('payment_method')
                                    ->label('Method')
                                    ->badge(),
                                TextEntry::make('reference_number')
                                    ->label('Reference')
                                    ->placeholder('-'),
                                TextEntry::make('creator.name')
                                    ->label('Recorded by')
                                    ->placeholder('-'),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
