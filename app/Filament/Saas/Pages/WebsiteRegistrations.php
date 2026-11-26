<?php

namespace App\Filament\Saas\Pages;

use App\Models\OnboardingDraft;
use App\Models\WebsiteRegistration;
use App\Services\ClientOnboardingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class WebsiteRegistrations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Client Management';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Website Registrations';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.saas.pages.website-registrations';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('organizations') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table->query(WebsiteRegistration::query()->with(['draft', 'checkout']))->columns([
            TextColumn::make('email')->searchable()->label('Applicant email'),
            TextColumn::make('draft.data.owner_name')->label('Contact'),
            TextColumn::make('draft.data.clinic_name')->label('Clinic'),
            TextColumn::make('draft.account_structure')->label('Structure')->badge(),
            TextColumn::make('draft.verification_model')->label('Offering')->formatStateUsing(fn ($state) => \App\Models\SubscriptionPlan::verificationModelOptions()[$state] ?? $state),
            TextColumn::make('draft.status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (string $state): string => WebsiteRegistration::statusLabel($state)),
            TextColumn::make('checkout.status')->label('Payment')->badge()->placeholder('Not started'),
            TextColumn::make('draft.last_completed_step')->label('Saved progress')->formatStateUsing(fn ($state) => [1 => 'Contact / partial details', 2 => 'Practice details', 3 => 'Plan selected', 4 => 'Submitted'][$state] ?? 'Started'),
            TextColumn::make('email_verified_at')->label('Email verified')->dateTime()->placeholder('Not verified'),
            TextColumn::make('draft.updated_at')->label('Last saved')->since(),
        ])->filters([
            SelectFilter::make('status')
                ->options(WebsiteRegistration::STATUS_LABELS)
                ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, fn ($q, $status) => $q->whereHas('draft', fn ($d) => $d->where('status', $status)))),
        ])->recordActions([
            Action::make('details')->label('Saved details')
                ->modalHeading('Saved website registration')
                ->modalSubmitAction(false)->modalCancelActionLabel('Close')
                ->modalContent(fn (WebsiteRegistration $record) => view('filament.saas.pages.website-registration-details', ['registration' => $record])),
            Action::make('review')->label('Review in onboarding')
                ->visible(fn ($record) => $record->email_verified_at && in_array($record->draft->status, ['submitted', 'under_review', 'changes_requested'], true) && auth()->user()->canPerformSaasModuleAction('organizations', 'update'))
                ->url(fn ($record) => app(ClientOnboardingService::class)->resumeUrl($record->draft)),
            Action::make('requestChanges')->label('Reopen for customer')->requiresConfirmation()
                ->modalDescription('The applicant can request a fresh resume link on the website to edit this registration. No email is sent by this action.')
                ->visible(fn ($record) => !$record->checkout && in_array($record->draft->status, ['submitted', 'under_review'], true) && auth()->user()->canPerformSaasModuleAction('organizations', 'update'))
                ->action(function (WebsiteRegistration $record) {
                    abort_unless(auth()->user()->canPerformSaasModuleAction('organizations', 'update'), 403);
                    \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                        $draft = OnboardingDraft::whereKey($record->onboarding_draft_id)->lockForUpdate()->firstOrFail();
                        abort_if($record->checkout()->exists(), 409, 'Checkout has started. Reconcile billing before changing the registration.');
                        if (in_array($draft->status, ['submitted', 'under_review'], true)) $draft->update(['status' => 'changes_requested', 'reviewed_at' => now()]);
                    });
                }),
        ])->defaultSort('updated_at', 'desc');
    }
}
