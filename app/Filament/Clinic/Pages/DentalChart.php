<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Resources\DentalChartEntries\DentalChartEntryResource;
use App\Models\DentalChartEntry;
use App\Models\Patient;
use App\Models\Provider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use UnitEnum;

class DentalChart extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Dental Charting';

    protected static ?string $navigationLabel = 'Dental Chart';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dental Chart';

    protected static ?string $slug = 'dental-chart';

    protected string $view = 'filament.clinic.pages.dental-chart';

    public ?array $data = [];

    public ?string $selectedTooth = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessClinicDentalCharting(), 403);

        $defaultPatientId = Patient::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->where('clinic_id', auth()->user()?->clinic_id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->value('id');

        $this->form->fill([
            'patient_id' => $defaultPatientId,
            'provider_id' => null,
            'chart_type' => null,
            'status' => null,
        ]);

        $this->selectedTooth = '8';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicDentalCharting() ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Chart Filters')
                    ->description('Pick a patient and filter the chart by provider, chart layer, or current treatment state.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('patient_id')
                                    ->label('Patient')
                                    ->options(fn (): array => Patient::query()
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Patient $patient): array => [$patient->id => $patient->full_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (?string $state): void {
                                        if (blank($state)) {
                                            $this->selectedTooth = null;

                                            return;
                                        }

                                        $this->selectedTooth = $this->selectedTooth ?: '8';
                                    }),
                                Select::make('provider_id')
                                    ->label('Provider')
                                    ->options(fn (): array => Provider::query()
                                        ->with('user')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(fn (Provider $provider): array => [$provider->id => $provider->display_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                                Select::make('chart_type')
                                    ->label('Chart layer')
                                    ->options(DentalChartEntry::CHART_TYPE_OPTIONS)
                                    ->native(false)
                                    ->live(),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(DentalChartEntry::STATUS_OPTIONS)
                                    ->native(false)
                                    ->live(),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newEntry')
                ->label('New chart entry')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => $this->buildCreateUrl()),
            Action::make('openEntries')
                ->label('Chart entry log')
                ->icon('heroicon-o-queue-list')
                ->url(fn (): string => DentalChartEntryResource::getUrl('index')),
        ];
    }

    public function selectTooth(string $tooth): void
    {
        $this->selectedTooth = $tooth;
    }

    public function getSummaryCards(): array
    {
        $entries = $this->filteredEntries();

        return [
            [
                'label' => 'Charted teeth',
                'value' => (string) $entries->pluck('tooth_number')->filter()->unique()->count(),
                'tone' => 'sky',
                'description' => 'Unique teeth carrying clinical chart activity in the current view.',
            ],
            [
                'label' => 'Planned items',
                'value' => (string) $entries->where('chart_type', 'planned')->count(),
                'tone' => 'amber',
                'description' => 'Planned restorative or treatment steps still in the chart.',
            ],
            [
                'label' => 'Completed items',
                'value' => (string) $entries->where('chart_type', 'completed')->count(),
                'tone' => 'emerald',
                'description' => 'Charted procedures already completed and documented.',
            ],
            [
                'label' => 'Watch list',
                'value' => (string) $entries->where('status', 'watch')->count(),
                'tone' => 'rose',
                'description' => 'Conditions being monitored or flagged for follow-up.',
            ],
        ];
    }

    public function getUpperArch(): array
    {
        return $this->buildArch(range(1, 16));
    }

    public function getLowerArch(): array
    {
        return $this->buildArch(range(32, 17));
    }

    public function getSelectedToothData(): ?array
    {
        if (blank($this->selectedTooth) || blank($this->getFilters()['patient_id'] ?? null)) {
            return null;
        }

        $entries = $this->filteredEntries()
            ->where('tooth_number', $this->selectedTooth)
            ->sortByDesc(fn (DentalChartEntry $entry) => optional($entry->recorded_on)?->timestamp ?? 0)
            ->values();

        $latest = $entries->first();

        return [
            'tooth' => $this->selectedTooth,
            'entries' => $entries->map(function (DentalChartEntry $entry): array {
                return [
                    'recorded_on' => optional($entry->recorded_on)?->format('M d, Y') ?? '-',
                    'chart_type' => DentalChartEntry::CHART_TYPE_OPTIONS[$entry->chart_type] ?? str($entry->chart_type)->title()->toString(),
                    'status' => DentalChartEntry::STATUS_OPTIONS[$entry->status] ?? str($entry->status)->title()->toString(),
                    'condition' => DentalChartEntry::CONDITION_CODE_OPTIONS[$entry->condition_code] ?? (filled($entry->condition_code) ? str($entry->condition_code)->replace('_', ' ')->title()->toString() : 'Custom note'),
                    'surface' => $entry->tooth_surface ?: '-',
                    'description' => $entry->description ?: 'No description',
                    'notes' => $entry->notes,
                    'provider' => $entry->provider?->display_name,
                    'edit_url' => DentalChartEntryResource::getUrl('edit', ['record' => $entry]),
                    'view_url' => DentalChartEntryResource::getUrl('view', ['record' => $entry]),
                ];
            })->all(),
            'latest' => $latest ? [
                'condition' => DentalChartEntry::CONDITION_CODE_OPTIONS[$latest->condition_code] ?? str($latest->condition_code)->replace('_', ' ')->title()->toString(),
                'chart_type' => DentalChartEntry::CHART_TYPE_OPTIONS[$latest->chart_type] ?? str($latest->chart_type)->title()->toString(),
                'status' => DentalChartEntry::STATUS_OPTIONS[$latest->status] ?? str($latest->status)->title()->toString(),
            ] : null,
            'create_urls' => [
                'condition' => $this->buildCreateUrl(['tooth_number' => $this->selectedTooth, 'chart_type' => 'condition']),
                'planned' => $this->buildCreateUrl(['tooth_number' => $this->selectedTooth, 'chart_type' => 'planned', 'status' => 'planned']),
                'completed' => $this->buildCreateUrl(['tooth_number' => $this->selectedTooth, 'chart_type' => 'completed', 'status' => 'completed']),
            ],
        ];
    }

    public function getActivePatient(): ?Patient
    {
        $patientId = $this->getFilters()['patient_id'] ?? null;

        if (blank($patientId)) {
            return null;
        }

        return Patient::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->where('clinic_id', auth()->user()?->clinic_id)
            ->find($patientId);
    }

    protected function buildArch(array $teeth): array
    {
        $entries = $this->filteredEntries()->groupBy('tooth_number');

        return collect($teeth)
            ->map(function (int $tooth) use ($entries): array {
                $toothNumber = (string) $tooth;
                $items = $entries->get($toothNumber, collect())->sortByDesc(fn (DentalChartEntry $entry) => optional($entry->recorded_on)?->timestamp ?? 0)->values();
                $latest = $items->first();

                return [
                    'number' => $toothNumber,
                    'selected' => $this->selectedTooth === $toothNumber,
                    'has_entries' => $items->isNotEmpty(),
                    'headline' => $latest ? (DentalChartEntry::CONDITION_CODE_OPTIONS[$latest->condition_code] ?? str($latest->condition_code)->replace('_', ' ')->title()->toString()) : 'Clear',
                    'subline' => $latest?->description ?: ($items->isNotEmpty() ? 'Charted item on file' : 'No findings recorded'),
                    'counts' => [
                        'planned' => $items->where('chart_type', 'planned')->count(),
                        'completed' => $items->where('chart_type', 'completed')->count(),
                        'watch' => $items->where('status', 'watch')->count(),
                    ],
                    'tone' => $this->resolveToothTone($items),
                ];
            })
            ->all();
    }

    protected function filteredEntries(): Collection
    {
        $filters = $this->getFilters();

        if (blank($filters['patient_id'] ?? null)) {
            return collect();
        }

        return DentalChartEntry::query()
            ->with(['provider.user'])
            ->where('organization_id', auth()->user()?->organization_id)
            ->where('clinic_id', auth()->user()?->clinic_id)
            ->where('patient_id', $filters['patient_id'])
            ->when($filters['provider_id'] ?? null, fn ($query, $providerId) => $query->where('provider_id', $providerId))
            ->when($filters['chart_type'] ?? null, fn ($query, $chartType) => $query->where('chart_type', $chartType))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('recorded_on')
            ->get();
    }

    protected function resolveToothTone(Collection $items): string
    {
        if ($items->isEmpty()) {
            return 'slate';
        }

        if ($items->contains(fn (DentalChartEntry $entry): bool => in_array($entry->condition_code, ['caries', 'fracture', 'perio_concern'], true))) {
            return 'rose';
        }

        if ($items->contains(fn (DentalChartEntry $entry): bool => $entry->chart_type === 'planned' || $entry->status === 'planned')) {
            return 'amber';
        }

        if ($items->contains(fn (DentalChartEntry $entry): bool => $entry->chart_type === 'completed' || $entry->status === 'completed')) {
            return 'emerald';
        }

        if ($items->contains(fn (DentalChartEntry $entry): bool => $entry->chart_type === 'existing')) {
            return 'sky';
        }

        return 'slate';
    }

    protected function buildCreateUrl(array $overrides = []): string
    {
        $filters = $this->getFilters();

        $query = array_filter([
            'patient_id' => $filters['patient_id'] ?? null,
            'provider_id' => $filters['provider_id'] ?? null,
            'location_id' => $this->getActivePatient()?->location_id,
            ...$overrides,
        ], fn ($value) => filled($value));

        return DentalChartEntryResource::getUrl('create') . (filled($query) ? ('?' . Arr::query($query)) : '');
    }

    protected function getFilters(): array
    {
        return is_array($this->data) ? $this->data : [];
    }
}
