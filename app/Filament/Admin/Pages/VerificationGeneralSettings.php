<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Support\AdminClinicScope;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.admin.pages.verification-general-settings';

    public ?array $data = [];

    protected ?Clinic $clinicRecord = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessVerificationWorkspace() ?? false;
    }

    public function mount(): void
    {
        $this->clinicRecord = AdminClinicScope::selectedClinic();

        $this->form->fill([
            'verification_default_form_template' => $this->clinicRecord?->getVerificationDefaultFormTemplate()
                ?? VerificationFormQuestion::defaultTemplateKey(),
            'allow_verification_manager_template_edits' => $this->clinicRecord?->allowsVerificationManagerTemplateEdits() ?? false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Clinic Verification Preferences')
                    ->description('Review the active clinic scope and control the template behavior used for new verification requests.')
                    ->schema([
                        Placeholder::make('clinic_scope')
                            ->label('Clinic')
                            ->content(fn (): string => $this->clinicRecord?->clinic_name ?? 'Select a clinic from the workspace selector.'),
                        Placeholder::make('organization_scope')
                            ->label('Organization')
                            ->content(fn (): string => $this->clinicRecord?->organization?->name ?? '-'),
                        Placeholder::make('service_model')
                            ->label('Verification service model')
                            ->content(fn (): string => $this->serviceModelLabel()),
                        Select::make('verification_default_form_template')
                            ->label('Default verification template')
                            ->options(VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS)
                            ->native(false)
                            ->required()
                            ->disabled(fn (): bool => ! $this->canManageClinicSettings()),
                        Toggle::make('allow_verification_manager_template_edits')
                            ->label('Allow Verification Manager template edits')
                            ->helperText('Allows assigned Verification Managers to create and publish clinic-specific template drafts.')
                            ->disabled(fn (): bool => ! $this->canManageClinicSettings()),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save General Settings')
                ->visible(fn (): bool => $this->canManageClinicSettings())
                ->action('save'),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Manage clinic verification preferences, connections, and administrative controls.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            VerificationRequestResource::getUrl('index') => 'Verification',
            'Settings',
        ];
    }

    public function save(): void
    {
        abort_unless($this->canManageClinicSettings(), 403);

        $clinic = AdminClinicScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic')
                ->body('Choose a clinic from the workspace selector before saving settings.')
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();
        $clinic->update([
            'verification_default_form_template' => $state['verification_default_form_template'],
            'allow_verification_manager_template_edits' => (bool) ($state['allow_verification_manager_template_edits'] ?? false),
        ]);

        $this->clinicRecord = $clinic->fresh('organization');

        Notification::make()
            ->title('General settings saved')
            ->body('Clinic verification preferences have been updated.')
            ->success()
            ->send();
    }

    public function getVerificationNavItems(): array
    {
        return \App\Support\VerificationSettingsNavigation::items();
    }

    public function canManageClinicSettings(): bool
    {
        return auth()->user()?->canManageVerificationSettings() ?? false;
    }

    protected function serviceModelLabel(): string
    {
        return match ($this->clinicRecord?->managed_services_status) {
            'active', 'trial' => 'Managed Service',
            'requested' => 'Hybrid / activation requested',
            default => 'Self-Managed',
        };
    }
}
