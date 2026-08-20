<?php

namespace App\Filament\Saas\Resources\Organizations\Pages;

use App\Filament\Saas\Pages\ClientManagement;
use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Models\Clinic;
use App\Models\Dso;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;

    protected string $view = 'filament.saas.resources.organizations.pages.list-organizations';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clientManagement')
                ->label('Client Management')
                ->icon('heroicon-o-building-office-2')
                ->color('primary')
                ->url(ClientManagement::getUrl()),
            CreateAction::make()
                ->label('Quick Add Organization')
                ->icon('heroicon-o-plus')
                ->color('gray'),
        ];
    }

    public function clientRegistryStats(): array
    {
        return [
            'organizations' => Organization::query()->count(),
            'active_organizations' => Organization::query()->where('status', true)->count(),
            'dsos' => Dso::query()->count(),
            'clinics' => Clinic::query()->count(),
            'locations' => Clinic::query()->withCount('locations')->get()->sum('locations_count'),
            'managed_clients' => Clinic::query()->whereIn('managed_services_status', ['active', 'trial'])->count(),
            'hybrid_or_requested' => Clinic::query()->where('managed_services_status', 'requested')->count(),
            'self_service' => Clinic::query()
                ->where('verification_services_enabled', true)
                ->whereNotIn('managed_services_status', ['active', 'trial', 'requested'])
                ->count(),
        ];
    }

    public function clientRegistryWorkflow(): array
    {
        return [
            [
                'label' => 'Register',
                'description' => 'Start from Client Management and choose Solo, Multi Location, or DSO before onboarding.',
            ],
            [
                'label' => 'Configure',
                'description' => 'Set verification model, organization ownership, clinic structure, users, and subscription.',
            ],
            [
                'label' => 'Operate',
                'description' => 'Verification work stays clinic-owned while reporting and policy roll up to the organization.',
            ],
            [
                'label' => 'Manage',
                'description' => 'Use this registry to inspect client records, then drill into clinics, users, billing, and activity.',
            ],
        ];
    }
}
