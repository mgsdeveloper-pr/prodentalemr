<?php

namespace App\Filament\Saas\Pages;

use App\Models\WebsiteCheckout;
use App\Services\WebsiteCheckout\CheckoutMoney;
use App\Services\WebsiteCheckout\CheckoutService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class WebsitePayments extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string|UnitEnum|null $navigationGroup = 'Billing';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Website Payments';
    protected string $view = 'filament.saas.pages.website-payments';

    public static function canAccess(): bool { return auth()->user()?->canAccessSaasModule('payments') ?? false; }
    public function table(Table $table): Table
    {
        return $table->query(WebsiteCheckout::with(['registration.draft', 'attempts']))->columns([
            TextColumn::make('public_id')->label('Checkout')->searchable()->copyable(),
            TextColumn::make('registration.email')->label('Customer')->searchable(),
            TextColumn::make('plan_snapshot.name')->label('Plan'),
            TextColumn::make('amount_minor')->label('Initial payment')->formatStateUsing(fn ($state, $record) => $record->currency.' '.CheckoutMoney::decimal((int) $state)),
            TextColumn::make('status')->badge(),
            TextColumn::make('latest_gateway')->label('Gateway / mode')->state(fn ($record) => ($a = $record->attempts->sortByDesc('id')->first()) ? $a->gateway.' / '.$a->environment : '—'),
            TextColumn::make('paid_at')->label('Settlement recorded')->dateTime()->placeholder('Not paid'),
            TextColumn::make('updated_at')->since(),
        ])->filters([
            SelectFilter::make('status')->options(array_combine($states = ['unpaid', 'pending', 'paid', 'failed', 'expired', 'refunded', 'partially_refunded', 'disputed', 'reversed', 'review_required'], $states)),
        ])->recordActions([
            Action::make('details')->label('Payment history')->modalSubmitAction(false)->modalCancelActionLabel('Close')
                ->modalContent(fn ($record) => view('filament.saas.pages.website-payment-details', ['checkout' => $record])),
            Action::make('refresh')->label('Check gateway status')
                ->visible(fn () => auth()->user()->canPerformSaasModuleAction('payments', 'update'))
                ->action(function (WebsiteCheckout $record) {
                    abort_unless(auth()->user()->canPerformSaasModuleAction('payments', 'update'), 403);
                    try {
                        app(CheckoutService::class)->refresh($record->registration);
                        Notification::make()->title('Payment status checked')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Could not verify payment')->body('The saved attempt was retained. Check provider credentials and the gateway dashboard before retrying.')->warning()->send();
                    }
                }),
        ])->defaultSort('updated_at', 'desc');
    }
}
