<?php

namespace App\Filament\Admin\Pages;

use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use App\Support\VerificationReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class VerificationReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Verification Reports';

    protected static ?string $slug = 'verification-reports';

    protected string $view = 'filament.shared.verification-reports';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessVerificationModule('reports') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'clinic_id' => AdminClinicScope::selectedClinicId(),
            'assigned_to' => null,
            'worked_by' => null,
            'status' => null,
            'outcome_status' => null,
            'priority' => null,
            'source' => null,
            'workflow_exception' => null,
            'form_type' => null,
            'insurance_status' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Report Filters')
                    ->description('Filter verification volume, turnaround, outcomes, and workload for the current reporting view.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                DatePicker::make('from_date')->label('From')->live(),
                                DatePicker::make('to_date')->label('To')->live(),
                                Select::make('clinic_id')
                                    ->label('Clinic')
                                    ->options(fn (): array => VerificationReport::clinicOptions())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('All clinics')
                                    ->live(),
                                Select::make('assigned_to')
                                    ->label('Assignee')
                                    ->options(fn (): array => VerificationReport::assigneeOptions($this->selectedClinicId()))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live(),
                                Select::make('worked_by')
                                    ->label('Worked By')
                                    ->options(fn (): array => VerificationReport::workedByOptions($this->selectedClinicId()))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live(),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(BillingWorkItem::STATUS_OPTIONS)
                                    ->native(false)
                                    ->live(),
                                Select::make('outcome_status')
                                    ->label('Outcome')
                                    ->options(BillingWorkItem::OUTCOME_STATUS_OPTIONS)
                                    ->native(false)
                                    ->live(),
                                Select::make('priority')
                                    ->label('Priority')
                                    ->options(BillingWorkItem::PRIORITY_OPTIONS)
                                    ->native(false)
                                    ->live(),
                                Select::make('source')
                                    ->label('Ownership')
                                    ->options(BillingWorkItem::SOURCE_OPTIONS)
                                    ->native(false)
                                    ->live(),
                                Select::make('workflow_exception')
                                    ->label('Workflow Exception')
                                    ->options(VerificationReport::workflowExceptionOptions())
                                    ->placeholder('Any exception state')
                                    ->native(false)
                                    ->live(),
                                Select::make('form_type')
                                    ->label('Form Type')
                                    ->options(VerificationReport::formTypeOptions())
                                    ->placeholder('All forms')
                                    ->native(false)
                                    ->live(),
                                Select::make('insurance_status')
                                    ->label('Insurance Status')
                                    ->options(VerificationReport::insuranceStatusOptions())
                                    ->placeholder('Any status')
                                    ->native(false)
                                    ->live(),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetFilters')
                ->label('Reset Filters')
                ->color('gray')
                ->action(function (): void {
                    $this->form->fill([
                        'from_date' => now()->startOfMonth()->toDateString(),
                        'to_date' => now()->toDateString(),
                        'clinic_id' => AdminClinicScope::selectedClinicId(),
                        'assigned_to' => null,
                        'worked_by' => null,
                        'status' => null,
                        'outcome_status' => null,
                        'priority' => null,
                        'source' => null,
                        'workflow_exception' => null,
                        'form_type' => null,
                        'insurance_status' => null,
                    ]);
                }),
            Action::make('downloadCsv')
                ->label('CSV')
                ->action(fn () => response()->streamDownload(
                    fn () => print(VerificationReport::csv($this->exportRows())),
                    'verification-reports.csv',
                    ['Content-Type' => 'text/csv']
                )),
            Action::make('downloadExcel')
                ->label('Excel')
                ->action(fn () => response()->streamDownload(
                    fn () => print(VerificationReport::excelHtml($this->exportRows(), $this->exportMeta())),
                    'verification-reports.xls',
                    ['Content-Type' => 'application/vnd.ms-excel']
                )),
            Action::make('downloadWord')
                ->label('Word')
                ->action(fn () => response()->streamDownload(
                    fn () => print(VerificationReport::wordHtml($this->exportRows(), $this->exportMeta())),
                    'verification-reports.doc',
                    ['Content-Type' => 'application/msword']
                )),
            Action::make('downloadPdf')
                ->label('PDF')
                ->action(fn () => response()->streamDownload(
                    fn () => print(VerificationReport::pdf($this->exportRows(), $this->exportMeta())),
                    'verification-reports.pdf',
                    ['Content-Type' => 'application/pdf']
                )),
        ];
    }

    public function getSummaryCards(): array
    {
        return VerificationReport::summaryCards($this->baseQuery());
    }

    public function getTrendChart(): array
    {
        return VerificationReport::trendChart($this->baseQuery(), $this->form->getState());
    }

    public function getStatusVisualization(): array
    {
        return VerificationReport::barVisualization(VerificationReport::statusBreakdown($this->baseQuery()));
    }

    public function getOutcomeVisualization(): array
    {
        return VerificationReport::barVisualization(VerificationReport::outcomeBreakdown($this->baseQuery()));
    }

    public function getSourceVisualization(): array
    {
        return VerificationReport::barVisualization(VerificationReport::sourceBreakdown($this->baseQuery()));
    }

    public function getAssigneeVisualization(): array
    {
        return VerificationReport::barVisualization(VerificationReport::assigneeBreakdown($this->baseQuery()));
    }

    public function getSlaAnalytics(): array
    {
        return VerificationReport::slaAnalytics($this->baseQuery());
    }

    public function getRecentRows(): array
    {
        return VerificationReport::recentRows($this->baseQuery(), 12);
    }

    public function applyActivityFocus(string $focus): void
    {
        $state = $this->form->getState();

        $state['status'] = null;
        $state['workflow_exception'] = null;

        match ($focus) {
            BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => $state['workflow_exception'] = BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
            BillingWorkItem::STATUS_RETURNED_FOR_REWORK => $state['workflow_exception'] = BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
            BillingWorkItem::STATUS_REVIEW => $state['status'] = BillingWorkItem::STATUS_REVIEW,
            default => null,
        };

        $this->form->fill($state);
    }

    public function clearActivityFocus(): void
    {
        $state = $this->form->getState();
        $state['status'] = null;
        $state['workflow_exception'] = null;

        $this->form->fill($state);
    }

    public function getActivityFocusChips(): array
    {
        return [
            [
                'key' => BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
                'label' => 'Waiting on Clinic',
                'active' => ($this->form->getState()['workflow_exception'] ?? null) === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
            ],
            [
                'key' => BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                'label' => 'Returned for Rework',
                'active' => ($this->form->getState()['workflow_exception'] ?? null) === BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
            ],
            [
                'key' => BillingWorkItem::STATUS_REVIEW,
                'label' => 'Review',
                'active' => ($this->form->getState()['status'] ?? null) === BillingWorkItem::STATUS_REVIEW,
            ],
        ];
    }

    public function getAppliedScopeLabel(): string
    {
        $state = $this->form->getState();
        $selectedClinic = VerificationReport::clinicOptions()[$state['clinic_id'] ?? null] ?? null;

        return $selectedClinic ?: 'All clinics';
    }

    protected function baseQuery(): Builder
    {
        $query = AdminClinicScope::apply(
            BillingWorkItem::query()
            ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
            ->where('source', '!=', 'clinic_self_service'),
            'billing_work_items.clinic_id'
        );

        return VerificationReport::applyFilters($query, $this->normalizedFilters());
    }

    protected function selectedClinicId(): ?int
    {
        $state = $this->form->getState();

        return filled($state['clinic_id'] ?? null) ? (int) $state['clinic_id'] : null;
    }

    protected function normalizedFilters(): array
    {
        $filters = $this->form->getState();

        if (blank($filters['clinic_id'] ?? null) && AdminClinicScope::selectedClinicId()) {
            $filters['clinic_id'] = AdminClinicScope::selectedClinicId();
        }

        return $filters;
    }

    protected function exportRows(): array
    {
        return VerificationReport::exportRows($this->baseQuery());
    }

    protected function exportMeta(): array
    {
        $filters = $this->normalizedFilters();

        return [
            'title' => 'Verification Reports',
            'generated_at' => now()->format('M d, Y h:i A'),
            'scope' => $this->getAppliedScopeLabel(),
            'date_range' => trim(
                collect([
                    filled($filters['from_date'] ?? null) ? Carbon::parse($filters['from_date'])->format('M d, Y') : null,
                    filled($filters['to_date'] ?? null) ? Carbon::parse($filters['to_date'])->format('M d, Y') : null,
                ])->filter()->implode(' to ')
            ),
        ];
    }
}
