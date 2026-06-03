<?php

namespace App\Filament\Admin\Pages;

use App\Models\SaasSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationAssignmentManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?string $navigationLabel = 'Assignment Management';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Assignment Management';

    protected static ?string $slug = 'verification-assignment-management';

    protected string $view = 'filament.admin.pages.verification-assignment-management';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageVerificationSettings() ?? false;
    }

    public function mount(): void
    {
        $settings = SaasSetting::current();

        $this->form->fill([
            'verification_round_robin_enabled' => (bool) $settings->verification_round_robin_enabled,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Assignment Rules')
                    ->description('Control how new managed-service verification requests are assigned when no user is selected manually.')
                    ->schema([
                        Toggle::make('verification_round_robin_enabled')
                            ->label('Enable round-robin auto assignment')
                            ->helperText('When enabled, new verification requests rotate evenly across eligible verification users. When disabled, the system falls back to the current lightest-workload assignment logic.')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save assignment rules')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        SaasSetting::current()->update([
            'verification_round_robin_enabled' => (bool) ($this->data['verification_round_robin_enabled'] ?? false),
        ]);

        Notification::make()
            ->title('Assignment management saved')
            ->body('Verification auto-assignment behavior has been updated successfully.')
            ->success()
            ->send();
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
                'key' => 'assignment',
                'label' => 'Assignment Management',
                'description' => 'Control how verification work is auto-assigned across the team.',
                'url' => static::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Maintain the shared insurance carrier master and clinic-specific defaults.',
                'url' => \App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource::getUrl('index'),
            ],
            [
                'key' => 'participation',
                'label' => 'Provider Participation',
                'description' => 'Manage participating and non-participating payer guidance for verifiers.',
                'url' => \App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource::getUrl('index'),
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
                'url' => \App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource::getUrl('index'),
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
                'url' => VerificationNotificationControl::getUrl(),
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
