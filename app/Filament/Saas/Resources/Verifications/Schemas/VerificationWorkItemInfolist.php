<?php

namespace App\Filament\Saas\Resources\Verifications\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VerificationWorkItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Verification Queue Snapshot')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('reference_number')->label('Reference #'),
                                TextEntry::make('managedBillingService.name')->label('Service'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('outcome_status')->label('Outcome')->badge()->placeholder('-'),
                                TextEntry::make('priority')->badge(),
                                TextEntry::make('assignedTo.name')->label('Assigned to')->placeholder('-'),
                                TextEntry::make('reviewedBy.name')->label('Reviewer')->placeholder('-'),
                                TextEntry::make('due_at')->dateTime('M d, Y h:i A')->placeholder('-'),
                            ]),
                    ]),
                Section::make('Practice and Patient Context')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('organization.name')->label('Organization'),
                                TextEntry::make('clinic.clinic_name')->label('Clinic')->placeholder('-'),
                                TextEntry::make('location.location_name')->label('Location')->placeholder('-'),
                                TextEntry::make('appointment.appointment_date')->label('Appointment date')->date()->placeholder('-'),
                                TextEntry::make('patient.full_name')->label('Patient')->placeholder('-'),
                                TextEntry::make('provider.display_name')->label('Provider')->placeholder('-'),
                                TextEntry::make('insurancePolicy.insurance_company')->label('Insurance provider')->placeholder('-'),
                                TextEntry::make('insurancePolicy.member_id')->label('Member ID')->placeholder('-'),
                            ]),
                    ]),
                Section::make('Verification Results')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('verificationProfile.form_type')->label('Form type')->placeholder('-'),
                                TextEntry::make('verificationProfile.subscriber_name')->label('Subscriber')->placeholder('-'),
                                TextEntry::make('verificationProfile.subscriber_id')->label('Subscriber ID')->placeholder('-'),
                                TextEntry::make('verificationProfile.group_number')->label('Group number')->placeholder('-'),
                                TextEntry::make('verificationProfile.network_status')->label('Network status')->placeholder('-'),
                                TextEntry::make('verificationProfile.fee_schedule')->label('Fee schedule')->placeholder('-'),
                                TextEntry::make('verificationProfile.plan_type')->label('Plan type')->placeholder('-'),
                                TextEntry::make('verificationProfile.payer_id')->label('Payer ID')->placeholder('-'),
                            ]),
                        TextEntry::make('verificationProfile.verification_notes')
                            ->label('Verification notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('verificationProfile.quick_reference')
                            ->label('Quick reference')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
