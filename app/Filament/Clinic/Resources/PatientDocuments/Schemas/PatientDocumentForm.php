<?php

namespace App\Filament\Clinic\Resources\PatientDocuments\Schemas;

use App\Models\Encounter;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\Provider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class PatientDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Hidden::make('disk')
                    ->default('local'),
                Section::make('Document Setup')
                    ->description('Store patient attachments on private storage with enough metadata for clinical review and auditability.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('patient_id')
                                    ->label('Patient')
                                    ->options(fn (): array => Patient::query()
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->full_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('document_type')
                                    ->label('Document type')
                                    ->options(PatientDocument::TYPE_OPTIONS)
                                    ->default('other')
                                    ->native(false)
                                    ->required(),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('title')
                                    ->label('Title')
                                    ->placeholder('Bitewing X-ray, Consent, Referral letter')
                                    ->maxLength(255),
                                Select::make('provider_id')
                                    ->label('Provider')
                                    ->options(fn (): array => Provider::query()
                                        ->with('user')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(fn (Provider $provider) => [$provider->id => $provider->display_name])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('encounter_id')
                                    ->label('Linked encounter')
                                    ->options(fn (): array => Encounter::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('encounter_date')
                                        ->get()
                                        ->mapWithKeys(fn (Encounter $encounter) => [$encounter->id => $encounter->encounter_date?->format('M d, Y') . ' · ' . ($encounter->patient?->full_name ?? 'Patient')])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                            ]),
                        FileUpload::make('path')
                            ->label('Secure file')
                            ->disk('local')
                            ->directory('patient-documents')
                            ->visibility('private')
                            ->downloadable(false)
                            ->openable(false)
                            ->previewable(true)
                            ->preserveFilenames()
                            ->required()
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'application/pdf',
                            ])
                            ->maxSize(20480)
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $disk = Storage::disk('local');
                                $set('original_name', basename((string) $state));
                                $set('mime_type', $disk->mimeType((string) $state) ?: 'application/octet-stream');
                                $set('file_size', (int) ($disk->size((string) $state) ?: 0));
                            })
                            ->columnSpanFull(),
                        Hidden::make('original_name'),
                        Hidden::make('mime_type'),
                        Hidden::make('file_size'),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
