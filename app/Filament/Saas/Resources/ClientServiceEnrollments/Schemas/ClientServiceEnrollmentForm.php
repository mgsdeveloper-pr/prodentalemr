<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Schemas;

use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ClientServiceEnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                Section::make('Enrollment Scope')
                    ->description('Turn on a managed SaaS billing service for a client clinic, with optional location-specific coverage.')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                Select::make('organization_id')
                                    ->label('Organization')
                                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->default(fn (): ?int => request()->integer('organization') ?: null)
                                    ->helperText('Choose the parent organization first to narrow the clinic and location list.')
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('clinic_id', null);
                                        $set('location_id', null);
                                    })
                                    ->required(),
                                Select::make('clinic_id')
                                    ->label('Clinic')
                                    ->options(fn (Get $get): array => Clinic::query()
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->orderBy('clinic_name')
                                        ->pluck('clinic_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->default(fn (): ?int => request()->integer('clinic') ?: null)
                                    ->disabled(fn (Get $get): bool => blank($get('organization_id')))
                                    ->helperText('A clinic is required because Verification access and assignments are clinic-specific.')
                                    ->afterStateUpdated(fn (Set $set) => $set('location_id', null))
                                    ->required(),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (Get $get): array => Location::query()
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (Get $get): bool => blank($get('clinic_id')))
                                    ->helperText('Optional. Select a location only when service coverage differs by location.'),
                            ]),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])
                            ->schema([
                                Select::make('managed_billing_service_id')
                                    ->label('Managed service')
                                    ->options(fn (): array => ManagedBillingService::query()
                                        ->where('status', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->default(fn (): ?int => request()->integer('service') ?: null)
                                    ->live()
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        $service = ManagedBillingService::query()->find($state);

                                        if (! $service) {
                                            return;
                                        }

                                        $set('urgent_sla_hours', max(1, (int) $service->service_level_agreement_hours));
                                    })
                                    ->required(),
                                Select::make('status')
                                    ->options(ClientServiceEnrollment::STATUS_OPTIONS)
                                    ->default('active')
                                    ->native(false)
                                    ->helperText('Only Active enrollments make a clinic available to the managed service team.')
                                    ->required(),
                                DatePicker::make('start_date')
                                    ->native(false)
                                    ->displayFormat('M d, Y'),
                                DatePicker::make('end_date')
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->afterOrEqual('start_date'),
                            ]),
                        Textarea::make('notes')
                            ->rows(4)
                            ->placeholder('Capture setup details, billing notes, client-specific handling, or any activation context your team should remember.')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                Section::make('Verification SLA')
                    ->description('Define the normal and urgent turnaround targets for verification work raised under this enrollment.')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Toggle::make('clinic_workspace_enabled')
                                    ->label('Clinic Workspace Enabled')
                                    ->helperText('When enabled, Clinic users can start, edit, and update the shared verification form while your service team works the same request in Admin.')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpanFull(),
                                TextInput::make('normal_sla_days')
                                    ->label('Normal SLA (days)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(3)
                                    ->required()
                                    ->columnSpan(6),
                                TextInput::make('urgent_sla_hours')
                                    ->label('Urgent SLA (hours)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(fn (): int => max(1, (int) (ManagedBillingService::query()
                                        ->whereKey(request()->integer('service'))
                                        ->value('service_level_agreement_hours') ?: 24)))
                                    ->required()
                                    ->columnSpan(6),
                            ]),
                    ])
                    ->columns(1),
            ]);
    }
}
