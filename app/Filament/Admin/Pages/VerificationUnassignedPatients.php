<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Support\VerificationAutoAssigner;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class VerificationUnassignedPatients extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?string $navigationLabel = 'Unassigned Patients';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Unassigned Patients';

    protected static ?string $slug = 'unassigned-patients';

    protected string $view = 'filament.admin.pages.verification-unassigned-patients';

    public function getHeading(): string
    {
        return '';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->canManageVerificationQueue()
            && $user?->canAccessVerificationModule('verification'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('Unassigned Verification Requests')
            ->description('Assign incoming verification requests that do not currently have an owner.')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<div style="display:flex;flex-direction:column;gap:2px;min-width:160px;">'
                        . '<span style="font-weight:700;color:#0f172a;">' . e($record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? '-')) . '</span>'
                        . '<span style="font-size:11px;color:#64748b;">' . e($record->reference_number ?: 'No reference') . '</span>'
                        . '</div>'
                    ))
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): void {
                        $query->where(function (Builder $innerQuery) use ($search): void {
                            $innerQuery->whereHas('patient', function (Builder $patientQuery) use ($search): void {
                                $patientQuery
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('pms_patient_id', 'like', "%{$search}%");
                            })->orWhereHas('verificationProfile', function (Builder $profileQuery) use ($search): void {
                                $profileQuery
                                    ->where('patient_full_name', 'like', "%{$search}%")
                                    ->orWhere('patient_identifier', 'like', "%{$search}%")
                                    ->orWhere('pms_id', 'like', "%{$search}%");
                            });
                        });
                    }),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('insurance_provider')
                    ->label('Insurance')
                    ->state(fn (BillingWorkItem $record): string => $record->verificationPlanSnapshots->first()?->payer_name
                        ?? $record->insurancePolicy?->insurance_company
                        ?? '-')
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'urgent' ? 'danger' : 'info')
                    ->formatStateUsing(fn (?string $state): string => $state === 'urgent' ? 'Urgent' : 'Normal')
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (BillingWorkItem $record): string => BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? 'Pending')
                    ->color(fn (BillingWorkItem $record): string => match ($record->normalized_status) {
                        BillingWorkItem::STATUS_PENDING => 'warning',
                        BillingWorkItem::STATUS_IN_PROGRESS => 'info',
                        BillingWorkItem::STATUS_REVIEW => 'primary',
                        BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => 'warning',
                        BillingWorkItem::STATUS_RETURNED_FOR_REWORK => 'danger',
                        BillingWorkItem::STATUS_INCOMPLETE => 'gray',
                        BillingWorkItem::STATUS_DONE => 'success',
                        default => 'gray',
                    })
                    ->alignCenter(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d-M-Y h:i A')
                    ->sortable(),
            ])
            ->actions([
                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form(fn (BillingWorkItem $record): array => [
                        Select::make('assigned_to')
                            ->label('Assign to')
                            ->options(VerificationAutoAssigner::optionList($record->clinic_id))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (BillingWorkItem $record, array $data): void {
                        abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                        $record->assigned_to = (int) $data['assigned_to'];
                        $record->save();

                        Notification::make()
                            ->title('Patient assigned')
                            ->body('The verification request was assigned successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('view')
                    ->label('View')
                    ->color('gray')
                    ->url(fn (BillingWorkItem $record): string => VerificationWorkItemResource::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->label('Edit')
                    ->color('gray')
                    ->url(fn (BillingWorkItem $record): string => VerificationWorkItemResource::getUrl('edit', ['record' => $record])),
            ])
            ->headerActions([
                Action::make('openVerificationList')
                    ->label('Open Verification List')
                    ->icon('heroicon-o-list-bullet')
                    ->color('gray')
                    ->url(VerificationWorkItemResource::getUrl('index')),
            ])
            ->emptyStateHeading('No unassigned patients')
            ->emptyStateDescription('Every verification request in your current clinic scope already has an assigned owner.');
    }

    protected function getTableQuery(): Builder
    {
        return VerificationWorkItemResource::getEloquentQuery()
            ->whereNull('assigned_to')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('status')
                    ->orWhereNotIn('status', [
                        BillingWorkItem::STATUS_DONE,
                        'completed',
                        'cancelled',
                    ]);
            });
    }
}
