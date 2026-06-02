<?php

namespace App\Filament\Saas\Pages;

use App\Support\ModuleWarnings;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SetupChecks extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Setup Checks';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Setup Checks';

    protected static ?string $slug = 'setup-checks';

    protected string $view = 'filament.saas.pages.setup-checks';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('settings') ?? false;
    }

    public function getRecordChecks(): array
    {
        return ModuleWarnings::recordChecks();
    }

    public function getCheckGroups(): array
    {
        $groups = [
            'Platform' => [
                'settings' => 'SaaS Settings',
                'notification-centre' => 'Notification Centre',
                'payment-credentials' => 'Payment Credentials',
                'billing-reports' => 'Billing Reports',
                'tenant-onboarding' => 'Organization Onboarding',
            ],
            'Tenant Management' => [
                'organizations' => 'Organizations',
                'clinics' => 'Clinics',
                'locations' => 'Locations',
            ],
            'Access Management' => [
                'users' => 'Users',
            ],
            'Billing' => [
                'subscription-plans' => 'Subscription Plans',
                'subscriptions' => 'Subscriptions',
                'invoices' => 'Invoices',
                'payments' => 'Payments',
            ],
        ];

        return collect($groups)
            ->map(function (array $modules, string $groupLabel): array {
                $items = collect($modules)
                    ->map(function (string $label, string $module): array {
                        $warnings = ModuleWarnings::for($module);

                        return [
                            'label' => $label,
                            'module' => $module,
                            'warnings' => $warnings,
                            'action' => ModuleWarnings::actionFor($module),
                            'count' => count($warnings),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'label' => $groupLabel,
                    'items' => $items,
                    'count' => collect($items)->sum('count'),
                ];
            })
            ->values()
            ->all();
    }

    public function getTotalWarnings(): int
    {
        return $this->getModuleWarningCount() + $this->getRecordWarningCount();
    }

    public function getModuleWarningCount(): int
    {
        return collect($this->getCheckGroups())->sum('count');
    }

    public function getRecordWarningCount(): int
    {
        return collect($this->getRecordChecks())->sum('count');
    }
}
