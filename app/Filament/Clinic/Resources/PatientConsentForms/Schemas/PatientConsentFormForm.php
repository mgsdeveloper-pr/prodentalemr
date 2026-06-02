<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms\Schemas;

use App\Models\Encounter;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientConsentForm;
use App\Models\Provider;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class PatientConsentFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Hidden::make('uploaded_by')
                    ->default(fn () => auth()->id()),
                Section::make('Consent Setup')
                    ->description('Capture HIPAA-aware acknowledgements, treatment consents, and patient-signed clinic forms with private storage and traceable metadata.')
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
                                Select::make('form_type')
                                    ->label('Form type')
                                    ->options(PatientConsentForm::FORM_TYPE_OPTIONS)
                                    ->default('treatment_consent')
                                    ->native(false)
                                    ->required(),
                                Select::make('status')
                                    ->options(PatientConsentForm::STATUS_OPTIONS)
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                TextInput::make('title')
                                    ->required()
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
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
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
                                        ->mapWithKeys(fn (Encounter $encounter) => [
                                            $encounter->id => collect([
                                                $encounter->encounter_date?->format('M d, Y'),
                                                $encounter->patient?->full_name,
                                            ])->filter()->implode(' - '),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                DatePicker::make('document_date')
                                    ->label('Document date')
                                    ->native(false)
                                    ->default(now()),
                                DatePicker::make('signed_on')
                                    ->label('Signed on')
                                    ->native(false),
                                DatePicker::make('expires_on')
                                    ->label('Expires on')
                                    ->native(false),
                                TextInput::make('signed_by_name')
                                    ->label('Signed by')
                                    ->maxLength(255),
                                TextInput::make('relationship_to_patient')
                                    ->label('Relationship to patient')
                                    ->maxLength(255),
                                TextInput::make('typed_signature')
                                    ->label('Typed signature')
                                    ->maxLength(255),
                            ]),
                        FileUpload::make('file_path')
                            ->label('Signed file / scan')
                            ->disk('local')
                            ->directory('patient-consent-forms')
                            ->visibility('private')
                            ->downloadable(false)
                            ->openable(false)
                            ->previewable(true)
                            ->preserveFilenames()
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
                                $set('original_filename', basename((string) $state));
                                $set('mime_type', $disk->mimeType((string) $state) ?: 'application/octet-stream');
                                $set('file_size', (int) ($disk->size((string) $state) ?: 0));
                            })
                            ->columnSpanFull(),
                        Hidden::make('original_filename'),
                        Hidden::make('mime_type'),
                        Hidden::make('file_size'),
                        Textarea::make('body_text')
                            ->label('Form body / acknowledgement text')
                            ->rows(6)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
