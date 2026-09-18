<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Services\Verification\PdfPresetService;
use App\Support\AdminClinicScope;
use App\Support\VerificationResultPdf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use UnitEnum;

class VerificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'PDF & Output';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'PDF & Output';

    protected static ?string $slug = 'verification-settings';

    protected string $view = 'filament.admin.pages.verification-settings';

    public ?array $data = [];

    #[Locked]
    public ?int $settingsClinicId = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageVerificationSettings() ?? false;
    }

    public function hasClinicScope(): bool
    {
        return $this->settingsClinicId !== null
            && $this->settingsClinicId === AdminClinicScope::selectedClinic()?->id;
    }

    public function getSelectedClinic(): ?Clinic
    {
        return $this->hasClinicScope() ? Clinic::find($this->settingsClinicId) : null;
    }

    public function getSubheading(): ?string
    {
        return $this->getSelectedClinic()?->clinic_name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            VerificationRequestResource::getUrl('index') => 'Verification',
            VerificationGeneralSettings::getUrl() => 'Settings',
            'PDF & Output',
        ];
    }

    public function mount(): void
    {
        $this->settingsClinicId = AdminClinicScope::selectedClinic()?->id;
        $this->form->fill(['verification_pdf_output_mode' => $this->getSavedMode()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Select::make('verification_pdf_output_mode')
                ->label('Default output')
                ->options(VerificationResultPdf::OUTPUT_MODE_OPTIONS)
                ->required()->selectablePlaceholder(false)->live()
                ->rules([Rule::in(array_keys(VerificationResultPdf::OUTPUT_MODE_OPTIONS))]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('save')->label('Save Default')
            ->visible(fn (): bool => $this->hasClinicScope())
            ->action(fn () => $this->save())];
    }

    public function getSavedMode(): string
    {
        $clinic = $this->getSelectedClinic();

        return $clinic
            ? (app(PdfPresetService::class)->defaultForClinic($clinic)?->getOutputMode() ?? $clinic->getVerificationPdfOutputMode())
            : 'standard';
    }

    public function getCurrentOutputLabel(): string
    {
        return VerificationResultPdf::OUTPUT_MODE_OPTIONS[$this->getSavedMode()];
    }

    public function getPreviewRecord(): ?BillingWorkItem
    {
        if (! static::canAccess() || ! $this->hasClinicScope()) {
            return null;
        }

        return BillingWorkItem::query()->where('clinic_id', $this->settingsClinicId)
            ->where('status', BillingWorkItem::STATUS_DONE)->latest('id')->cursor()
            ->first(fn (BillingWorkItem $item): bool => auth()->user()->can('view', $item));
    }

    public function getPreviewUrl(BillingWorkItem $record): string
    {
        return route('admin.verifications.pdf.preview', [
            'billingWorkItem' => $record,
            'mode' => VerificationResultPdf::normalizeOutputMode($this->data['verification_pdf_output_mode'] ?? 'standard'),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess() && $this->hasClinicScope(), 403);
        $state = $this->form->getState();
        $clinic = $this->getSelectedClinic();
        app(PdfPresetService::class)->setDefaultOutputMode($clinic, $state['verification_pdf_output_mode']);
        Cache::forget("admin_clinic_scope.selected_clinic.{$clinic->id}");
        Notification::make()->title('Default output saved')->success()->send();
    }
}
