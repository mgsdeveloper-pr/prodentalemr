<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\Schemas;

use App\Models\BillingWorkItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillingWorkItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Queue Snapshot')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('reference_number')->label('Reference #'),
                                TextEntry::make('managedBillingService.name')->label('Managed service'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('outcome_status')
                                    ->label('Outcome')
                                    ->badge()
                                    ->state(fn (BillingWorkItem $record): ?string => $record->outcome_status),
                                TextEntry::make('priority')->badge(),
                                TextEntry::make('source')->badge(),
                                TextEntry::make('pms_sync_status')->label('PMS sync')->badge(),
                                TextEntry::make('writeback_status')->label('Writeback')->badge(),
                                TextEntry::make('assignedTo.name')->label('Assigned to')->placeholder('-'),
                                TextEntry::make('reviewedBy.name')->label('Reviewer')->placeholder('-'),
                                TextEntry::make('due_at')->dateTime('M d, Y h:i A')->placeholder('-'),
                                TextEntry::make('completed_at')->dateTime('M d, Y h:i A')->placeholder('-'),
                            ]),
                        TextEntry::make('title')
                            ->columnSpanFull(),
                    ]),
                Section::make('Client Context')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('organization.name')->label('Organization'),
                                TextEntry::make('clinic.clinic_name')->label('Clinic')->placeholder('-'),
                                TextEntry::make('location.location_name')->label('Location')->placeholder('-'),
                                TextEntry::make('enrollment.display_title')->label('Enrollment')->placeholder('-'),
                                TextEntry::make('patient.full_name')->label('Patient')->placeholder('-'),
                                TextEntry::make('provider.display_name')->label('Provider')->placeholder('-'),
                                TextEntry::make('appointment.appointment_date')->label('Appointment date')->date()->placeholder('-'),
                                TextEntry::make('insurancePolicy.insurance_company')->label('Insurance policy')->placeholder('-'),
                                TextEntry::make('insuranceClaim.claim_number')->label('Claim #')->placeholder('-'),
                            ]),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->placeholder('-'),
                        TextEntry::make('internal_summary')->label('Internal summary')->placeholder('-'),
                    ]),
            ]);
    }
}
