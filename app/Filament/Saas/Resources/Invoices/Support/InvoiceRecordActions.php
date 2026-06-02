<?php

namespace App\Filament\Saas\Resources\Invoices\Support;

use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Support\PayPalGateway;
use App\Support\SaasNotifications;
use App\Support\StripeGateway;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class InvoiceRecordActions
{
    public static function downloadPdf(): Action
    {
        return Action::make('downloadPdf')
            ->label('Download')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->url(fn (Invoice $record): string => route('saas.invoices.pdf.download', $record), shouldOpenInNewTab: false);
    }

    public static function viewPdf(): Action
    {
        return Action::make('viewPdf')
            ->label('View PDF')
            ->icon(Heroicon::OutlinedEye)
            ->url(fn (Invoice $record): string => route('saas.invoices.pdf.view', $record), shouldOpenInNewTab: true);
    }

    public static function send(): Action
    {
        return Action::make('sendInvoiceEmail')
            ->label('Send')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->action(function (Invoice $record): void {
                if (! SaasNotifications::sendInvoiceEmail($record->fresh(['organization', 'items', 'subscription.subscriptionPlan']), auth()->user())) {
                    Notification::make()
                        ->title('Invoice email not sent')
                        ->body('Add an organization email address and complete the Notification Centre mail settings first.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Invoice sent')
                    ->body("Invoice {$record->invoice_number} was emailed to {$record->organization?->email}.")
                    ->success()
                    ->send();
            })
            ->visible(fn (Invoice $record): bool => ! $record->trashed());
    }

    public static function copyPaymentLink(): Action
    {
        return Action::make('copyPaymentLink')
            ->label('Copy Stripe Link')
            ->icon(Heroicon::OutlinedClipboardDocument)
            ->fillForm(fn (Invoice $record): array => [
                'payment_link' => StripeGateway::paymentPageUrl($record),
            ])
            ->schema([
                TextInput::make('payment_link')
                    ->label('Payment page URL')
                    ->readOnly()
                    ->maxLength(65535)
                    ->helperText('Copy this signed payment page URL and share it with the organization.'),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->visible(fn (Invoice $record): bool => ! $record->trashed() && $record->status !== 'cancelled' && $record->balance_due > 0 && StripeGateway::canCreatePaymentLinks());
    }

    public static function paymentPage(): Action
    {
        return Action::make('paymentPage')
            ->label('View Stripe Page')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->url(fn (Invoice $record): string => StripeGateway::paymentPageUrl($record), shouldOpenInNewTab: true)
            ->visible(fn (Invoice $record): bool => ! $record->trashed() && $record->status !== 'cancelled' && $record->balance_due > 0 && StripeGateway::canCreatePaymentLinks());
    }

    public static function copyPayPalLink(): Action
    {
        return Action::make('copyPayPalLink')
            ->label('Copy PayPal Link')
            ->icon(Heroicon::OutlinedClipboardDocument)
            ->fillForm(fn (Invoice $record): array => [
                'payment_link' => PayPalGateway::paymentPageUrl($record),
            ])
            ->schema([
                TextInput::make('payment_link')
                    ->label('PayPal payment page URL')
                    ->readOnly()
                    ->maxLength(65535)
                    ->helperText('Copy this signed PayPal payment page URL and share it with the organization.'),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->visible(fn (Invoice $record): bool => ! $record->trashed() && $record->status !== 'cancelled' && $record->balance_due > 0 && PayPalGateway::canCreatePaymentLinks());
    }

    public static function payPalPage(): Action
    {
        return Action::make('payPalPage')
            ->label('View PayPal Page')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->url(fn (Invoice $record): string => PayPalGateway::paymentPageUrl($record), shouldOpenInNewTab: true)
            ->visible(fn (Invoice $record): bool => ! $record->trashed() && $record->status !== 'cancelled' && $record->balance_due > 0 && PayPalGateway::canCreatePaymentLinks());
    }

    public static function paymentReminder(): Action
    {
        return Action::make('paymentReminder')
            ->label('Payment Reminder')
            ->icon(Heroicon::OutlinedBell)
            ->action(function (Invoice $record): void {
                if (! SaasNotifications::sendInvoicePaymentReminder($record->fresh(['organization']), auth()->user())) {
                    Notification::make()
                        ->title('Payment reminder not sent')
                        ->body('Configure Stripe payment credentials, complete mail settings, and add an organization email address first.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Payment reminder sent')
                    ->body("A payment reminder for {$record->invoice_number} was emailed to {$record->organization?->email}.")
                    ->success()
                    ->send();
            })
            ->visible(fn (Invoice $record): bool => ! $record->trashed() && $record->status !== 'cancelled' && $record->balance_due > 0 && StripeGateway::canCreatePaymentLinks());
    }

    public static function cancel(): Action
    {
        return Action::make('cancelInvoice')
            ->label('Cancel')
            ->icon(Heroicon::OutlinedXMark)
            ->requiresConfirmation()
            ->action(function (Invoice $record): void {
                $record->status = 'cancelled';
                $record->save();

                SaasNotifications::invoiceUpdated($record->fresh(['organization', 'items', 'subscription.subscriptionPlan']), auth()->user());

                Notification::make()
                    ->title('Invoice cancelled')
                    ->body("Invoice {$record->invoice_number} was marked as cancelled.")
                    ->success()
                    ->send();
            })
            ->visible(fn (Invoice $record): bool => ! $record->trashed() && $record->status !== 'cancelled');
    }

    public static function addPayment(): Action
    {
        return Action::make('addPayment')
            ->label('Add Payment')
            ->icon(Heroicon::OutlinedPlus)
            ->schema([
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
                    ->options([
                        'manual' => 'Manual',
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'card' => 'Card',
                        'check' => 'Check',
                        'other' => 'Other',
                    ])
                    ->default('manual')
                    ->required()
                    ->native(false),
                TextInput::make('reference_number')
                    ->label('Reference number')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ])
            ->action(function (array $data, Invoice $record): void {
                Payment::create([
                    'invoice_id' => $record->id,
                    'organization_id' => $record->organization_id,
                    'payment_date' => $data['payment_date'],
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $record->refreshPaymentSummary();
                SaasNotifications::invoiceUpdated($record->fresh(['organization', 'items', 'subscription.subscriptionPlan']), auth()->user());

                Notification::make()
                    ->title('Payment recorded')
                    ->body("Payment of $" . number_format((float) $data['amount'], 2) . " was added to invoice {$record->invoice_number}.")
                    ->success()
                    ->send();
            })
            ->visible(fn (Invoice $record): bool => ! $record->trashed() && $record->status !== 'cancelled' && $record->balance_due > 0);
    }

    public static function duplicate(): Action
    {
        return Action::make('duplicateInvoice')
            ->label('Create Duplicate')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->action(function (Invoice $record): void {
                $record->loadMissing('items');

                $duplicate = $record->replicate([
                    'invoice_number',
                    'status',
                    'paid_at',
                ]);

                $duplicate->invoice_number = null;
                $duplicate->status = 'draft';
                $duplicate->paid_at = null;
                $duplicate->amount_paid = 0;
                $duplicate->balance_due = $record->total_amount;
                $duplicate->save();

                foreach ($record->items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $duplicate->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->line_total,
                    ]);
                }

                SaasNotifications::invoiceCreated($duplicate->fresh(['organization', 'items', 'subscription.subscriptionPlan']), auth()->user());

                Notification::make()
                    ->title('Invoice duplicated')
                    ->body("Draft invoice {$duplicate->invoice_number} was created.")
                    ->success()
                    ->send();
            })
            ->visible(fn (Invoice $record): bool => ! $record->trashed());
    }

    public static function softDelete(): DeleteAction
    {
        return DeleteAction::make()
            ->icon(Heroicon::OutlinedTrash)
            ->after(function (Invoice $record): void {
                SaasNotifications::invoiceDeleted($record->invoice_number, $record->organization?->name, auth()->user());
            })
            ->visible(fn (Invoice $record): bool => ! $record->trashed());
    }

    public static function pageDownloadPdf(Invoice $record): Action
    {
        return static::downloadPdf()
            ->record($record);
    }

    public static function pageViewPdf(Invoice $record): Action
    {
        return static::viewPdf()
            ->record($record);
    }

    public static function pageSend(Invoice $record): Action
    {
        return static::send()
            ->record($record);
    }

    public static function pageCancel(Invoice $record): Action
    {
        return static::cancel()
            ->record($record);
    }

    public static function pageAddPayment(Invoice $record): Action
    {
        return static::addPayment()
            ->record($record);
    }

    public static function pageCopyPaymentLink(Invoice $record): Action
    {
        return static::copyPaymentLink()
            ->record($record);
    }

    public static function pagePaymentPage(Invoice $record): Action
    {
        return static::paymentPage()
            ->record($record);
    }

    public static function pageCopyPayPalLink(Invoice $record): Action
    {
        return static::copyPayPalLink()
            ->record($record);
    }

    public static function pagePayPalPage(Invoice $record): Action
    {
        return static::payPalPage()
            ->record($record);
    }

    public static function pagePaymentReminder(Invoice $record): Action
    {
        return static::paymentReminder()
            ->record($record);
    }

    public static function pageDuplicate(Invoice $record): Action
    {
        return static::duplicate()
            ->record($record);
    }

    public static function pageSoftDelete(Invoice $record): DeleteAction
    {
        return static::softDelete()
            ->record($record);
    }

    public static function editUrl(Invoice $record): string
    {
        return InvoiceResource::getUrl('edit', ['record' => $record]);
    }

    public static function viewUrl(Invoice $record): string
    {
        return InvoiceResource::getUrl('view', ['record' => $record]);
    }
}
