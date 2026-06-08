<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\SaasSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationNotificationControl extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Alerts & Notifications';

    protected static ?string $navigationLabel = 'Notification Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Notification Settings';

    protected static ?string $slug = 'verification-notification-control';

    protected string $view = 'filament.admin.pages.verification-notification-control';

    public ?array $data = [];

    protected SaasSetting $settings;

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageVerificationNotifications() ?? false;
    }

    public function mount(): void
    {
        $this->settings = SaasSetting::current();
        $settings = $this->settings->only($this->settingKeys());
        $settings['verification_notify_on_new_request'] = (bool) (
            $this->settings->verification_notify_on_managed_service_requested
            || $this->settings->verification_notify_on_clinic_self_service_created
            || $this->settings->verification_notify_on_verification_request_created
            || $this->settings->verification_notify_on_admin_import_created
        );

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Recipient Rules')
                    ->description('Managed-service verification stays with Verification admins/managers and the assigned user. Clinic notifications remain limited to self-service work only.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('verification_notify_admin_all')
                                ->label('Admin sees all verification notifications')
                                ->default(true),
                            Toggle::make('verification_notify_assigned_user')
                                ->label('Assigned user receives work notifications')
                                ->default(true),
                            Toggle::make('verification_notify_clinic_self_service')
                                ->label('Clinic receives self-service notifications')
                                ->default(true),
                        ]),
                    ]),
                Section::make('Event Controls')
                    ->description('Keep only the live verification alerts that matter to the current managed-service workflow.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('verification_notify_on_new_request')
                                ->label('New patient added for verification')
                                ->default(true),
                            Toggle::make('verification_notify_on_assignment_changed')->label('Assigned to user')->default(true),
                            Toggle::make('verification_notify_on_status_changed')->label('Verification status changed')->default(true),
                            Toggle::make('verification_notify_on_sla_alert')->label('SLA alert')->default(true),
                        ]),
                    ]),
                Section::make('Urgent Alerts')
                    ->description('Urgent requests are highlighted separately so the assigned team can react quickly.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('verification_notify_on_urgent_flagged')
                                ->label('Urgent priority flagged')
                                ->default(true),
                            Toggle::make('verification_notify_on_urgent_assigned')
                                ->label('Urgent request assigned')
                                ->default(true),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save notification control')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $newRequestEnabled = (bool) ($state['verification_notify_on_new_request'] ?? true);

        $state['verification_notify_clinic_workspace'] = false;
        $state['verification_notify_on_managed_service_requested'] = $newRequestEnabled;
        $state['verification_notify_on_clinic_self_service_created'] = $newRequestEnabled;
        $state['verification_notify_on_verification_request_created'] = $newRequestEnabled;
        $state['verification_notify_on_admin_import_created'] = $newRequestEnabled;
        $state['verification_notify_on_outcome_changed'] = false;
        $state['verification_notify_on_clinic_verification_updated'] = false;
        $state['verification_notify_on_verification_profile_saved'] = false;
        $state['verification_notify_on_verification_pdf_download'] = false;
        $state['verification_notify_on_verification_pdf_preview'] = false;
        unset($state['verification_notify_on_new_request']);

        $this->settings->update($state);

        Notification::make()
            ->title('Verification notification control saved')
            ->body('Recipient rules, event toggles, and urgent alert settings have been updated.')
            ->success()
            ->send();
    }

    protected function settingKeys(): array
    {
        return [
            'verification_notify_admin_all',
            'verification_notify_assigned_user',
            'verification_notify_clinic_self_service',
            'verification_notify_on_new_request',
            'verification_notify_on_managed_service_requested',
            'verification_notify_on_clinic_self_service_created',
            'verification_notify_on_verification_request_created',
            'verification_notify_on_admin_import_created',
            'verification_notify_on_assignment_changed',
            'verification_notify_on_status_changed',
            'verification_notify_on_outcome_changed',
            'verification_notify_on_clinic_verification_updated',
            'verification_notify_on_verification_profile_saved',
            'verification_notify_on_verification_pdf_download',
            'verification_notify_on_verification_pdf_preview',
            'verification_notify_on_urgent_flagged',
            'verification_notify_on_urgent_assigned',
            'verification_notify_on_sla_alert',
        ];
    }

    public function getVerificationNavItems(): array
    {
        return [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => VerificationSettings::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Maintain the shared insurance carrier master and clinic-specific defaults.',
                'url' => InsuranceCarrierResource::getUrl('index'),
            ],
            [
                'key' => 'participation',
                'label' => 'Provider Participation',
                'description' => 'Manage participating and non-participating payer guidance for verifiers.',
                'url' => InsuranceCarrierNetworkProfileResource::getUrl('index'),
            ],
            [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'description' => 'Maintain the shared portal credential vault clinics can inherit from.',
                'url' => PortalCredentialSettings::getUrl(),
            ],
            [
                'key' => 'questions',
                'label' => 'Verification Questions',
                'description' => 'Manage prompts and section-specific question content.',
                'url' => VerificationFormQuestionResource::getUrl('index'),
            ],
            [
                'key' => 'arrangement',
                'label' => 'Question Arrangement',
                'description' => 'Reorder questions inside each verification section.',
                'url' => VerificationQuestionArrangement::getUrl(),
            ],
            [
                'key' => 'notifications',
                'label' => 'Notification Control',
                'description' => 'Manage verification events, recipients, and urgent alert behavior.',
                'url' => static::getUrl(),
            ],
            [
                'key' => 'readiness',
                'label' => 'Verification Readiness',
                'description' => 'Review launch blockers, polish items, and readiness gaps.',
                'url' => VerificationReadiness::getUrl(),
            ],
        ];
    }
}
