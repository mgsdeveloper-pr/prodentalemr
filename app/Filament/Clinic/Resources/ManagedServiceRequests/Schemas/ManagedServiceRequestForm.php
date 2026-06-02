<?php

namespace App\Filament\Clinic\Resources\ManagedServiceRequests\Schemas;

use App\Models\Location;
use App\Models\ManagedBillingService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManagedServiceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([
                Hidden::make('organization_id')->default($user?->organization_id),
                Hidden::make('clinic_id')->default($user?->clinic_id),
                Hidden::make('created_by')->default($user?->id),
                Hidden::make('status')->default('requested'),
                Section::make('Managed Service Opt-In')
                    ->description('Choose the paid managed service you want our team to handle for this clinic or location.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('organization')
                                    ->label('Organization')
                                    ->content($user?->organization?->name ?? '-'),
                                Placeholder::make('clinic')
                                    ->label('Clinic')
                                    ->content($user?->clinic?->clinic_name ?? '-'),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', $user?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Whole clinic'),
                                Select::make('managed_billing_service_id')
                                    ->label('Paid service')
                                    ->options(fn (): array => ManagedBillingService::query()
                                        ->where('status', true)
                                        ->orderBy('category')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (ManagedBillingService $service): array => [
                                            $service->id => $service->name . ' (' . (ManagedBillingService::CATEGORY_OPTIONS[$service->category] ?? str($service->category)->title()->toString()) . ')',
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Textarea::make('notes')
                            ->label('Service notes')
                            ->rows(5)
                            ->placeholder('Share service goals, payer focus, turnaround expectations, or special handling instructions for our team.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
