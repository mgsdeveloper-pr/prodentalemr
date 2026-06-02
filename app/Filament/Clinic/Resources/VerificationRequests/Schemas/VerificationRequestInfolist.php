<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Schemas;

use App\Models\BillingWorkItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VerificationRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Status')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('reference_number')->label('Reference #'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('outcome_status')->label('Verification outcome')->badge(),
                                TextEntry::make('priority')->badge(),
                                TextEntry::make('ownership_display')
                                    ->label('Ownership')
                                    ->state(fn (BillingWorkItem $record): string => static::ownershipLabel($record) . ' | ' . static::ownerName($record)),
                                TextEntry::make('reviewedBy.name')->label('Reviewer')->placeholder('-'),
                                TextEntry::make('due_at')->label('Requested by')->dateTime('M d, Y h:i A')->placeholder('-'),
                                TextEntry::make('created_at')->label('Created')->dateTime('M d, Y h:i A'),
                            ]),
                    ]),
                Section::make('Request Context')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('location_display')->label('Location')->state(fn (BillingWorkItem $record): string => $record->location?->location_name ?: ($record->verificationProfile?->location_name ?: '-')),
                                TextEntry::make('managedBillingService.name')->label('Service')->placeholder('-'),
                                TextEntry::make('patient.full_name')->label('Patient')->placeholder('-'),
                                TextEntry::make('provider_display')->label('Provider')->state(fn (BillingWorkItem $record): string => $record->provider?->display_name ?: ($record->verificationProfile?->provider_name ?: '-')),
                                TextEntry::make('appointment.appointment_date')->label('Appointment date')->date()->placeholder('-'),
                                TextEntry::make('insurancePolicy.insurance_company')->label('Insurance provider')->placeholder('-'),
                                TextEntry::make('insurancePolicy.member_id')->label('Member ID')->placeholder('-'),
                                TextEntry::make('source')->label('Source')->badge(),
                            ]),
                        TextEntry::make('notes')
                            ->label('Request details')
                            ->placeholder('No clinic-side notes were included.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Verification Summary')
                    ->schema([
                        TextEntry::make('verificationProfile.verification_notes')
                            ->label('Verification notes')
                            ->placeholder('Structured verification notes have not been added yet.')
                            ->columnSpanFull(),
                        TextEntry::make('verificationProfile.quick_reference')
                            ->label('Quick reference')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function ownershipLabel(BillingWorkItem $record): string
    {
        return $record->source === 'clinic_request'
            ? 'Service'
            : 'Clinic';
    }

    protected static function ownerName(BillingWorkItem $record): string
    {
        if ($record->source === 'clinic_request') {
            return $record->assignedTo?->name ?: 'Pending Assignment';
        }

        return $record->verificationProfile?->requested_by_name
            ?: $record->creator?->name
            ?: 'Clinic Team';
    }
}
