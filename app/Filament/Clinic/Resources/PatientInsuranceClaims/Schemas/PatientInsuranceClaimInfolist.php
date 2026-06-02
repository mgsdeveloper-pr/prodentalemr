<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInsuranceClaimInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Snapshot')
                    ->description('A compact claim overview for dental billing follow-up, payer communication, and treatment-finance coordination.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('claim_number')
                                    ->label('Claim number'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('claim_type')
                                    ->label('Claim type')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('insurancePolicy.insurance_company')
                                    ->label('Insurance')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('claim_date')
                                    ->label('Claim date')
                                    ->date('M d, Y'),
                                TextEntry::make('service_date')
                                    ->label('Service date')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('submitted_at')
                                    ->label('Submitted on')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('payer_reference')
                                    ->label('Payer reference')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Amounts')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('billed_amount')
                                    ->label('Billed')
                                    ->money('USD'),
                                TextEntry::make('estimated_coverage')
                                    ->label('Estimated coverage')
                                    ->money('USD'),
                                TextEntry::make('insurance_paid')
                                    ->label('Insurance paid')
                                    ->money('USD'),
                                TextEntry::make('patient_responsibility')
                                    ->label('Patient responsibility')
                                    ->money('USD'),
                            ]),
                    ]),
                Section::make('Claim Procedures')
                    ->schema([
                        TextEntry::make('line_items_snapshot')
                            ->label('')
                            ->state(function ($record): string {
                                $lines = $record->lineItems;

                                if ($lines->isEmpty()) {
                                    return 'No claim line items recorded yet.';
                                }

                                return $lines->map(function ($line): string {
                                    $procedure = collect([
                                        $line->procedure_code,
                                        $line->description,
                                        $line->tooth_number ? 'Tooth ' . $line->tooth_number : null,
                                        $line->tooth_surface,
                                    ])->filter()->implode(' - ');

                                    return $procedure
                                        . ' | Billed $' . number_format((float) $line->billed_amount, 2)
                                        . ' | Est. $' . number_format((float) $line->estimated_coverage, 2)
                                        . ' | Paid $' . number_format((float) $line->insurance_paid, 2);
                                })->implode("\n");
                            })
                            ->html()
                            ->formatStateUsing(fn (string $state): string => nl2br(e($state))),
                    ]),
                Section::make('Narrative')
                    ->schema([
                        TextEntry::make('procedure_summary')
                            ->label('Procedure summary')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('-'),
                    ]),
            ])
            ->columns(1);
    }
}
