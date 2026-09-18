<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\SaasSetting;
use App\Support\VerificationSettingsNavigation;
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

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Verification Workspace';

    protected static ?string $navigationLabel = 'Assignment Management';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Assignment Management';

    protected static ?string $slug = 'verification-assignment-management';

    protected string $view = 'filament.admin.pages.verification-assignment-management';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (auth()->user()?->isSaasAdmin() ?? false)
            && (auth()->user()?->canManageVerificationSettings() ?? false);
    }

    public function getSubheading(): ?string
    {
        return 'Platform-wide: choose the strategy used when Auto assignment is requested. Unassigned requests remain unassigned.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            VerificationRequestResource::getUrl('index') => 'Verification',
            VerificationGeneralSettings::getUrl() => 'Settings',
            'Assignment Rules',
        ];
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
                    ->description('Applies across clinics only when Auto assignment is selected. Manual and Unassigned choices are unchanged.')
                    ->schema([
                        Toggle::make('verification_round_robin_enabled')
                            ->label('Enable round-robin auto assignment')
                            ->helperText('Auto mode uses round-robin when enabled, or lightest workload when disabled. This switch does not enable assignment for Unassigned requests.')
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
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();
        SaasSetting::current()->update([
            'verification_round_robin_enabled' => (bool) ($state['verification_round_robin_enabled'] ?? false),
        ]);

        Notification::make()
            ->title('Assignment management saved')
            ->body('Verification auto-assignment behavior has been updated successfully.')
            ->success()
            ->send();
    }

    public function getVerificationNavItems(): array
    {
        return VerificationSettingsNavigation::items();
    }
}
