<?php

namespace App\Filament\Clinic\Pages;

use App\Models\Clinic;
use App\Models\Location;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use App\Support\SaasSupportAccess;
use App\Support\UsTimezoneOptions;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class OrganizationOperations extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Clinic Management';

    protected static ?string $navigationLabel = 'Clinic Profile';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected static ?string $slug = 'organization-operations';

    protected string $view = 'filament.clinic.pages.clinic-profile';

    public ?array $data = [];

    protected ?Clinic $clinic = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $clinic = $user?->shouldBypassClinicScope()
            ? ClinicPanelScope::initializeFor($user)
            : ClinicWorkspace::clinicForUser($user);

        return filled(ClinicWorkspace::enabledWorkspaces($clinic));
    }

    public function mount(): void
    {
        $this->clinic = ClinicPanelScope::selectedClinic();

        abort_unless($this->clinic instanceof Clinic, 404);

        $this->form->fill($this->clinic->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Clinic Profile')
                    ->persistTabInQueryString('profile-tab')
                    ->disabled(fn (): bool => ! $this->canEditProfile())
                    ->tabs([
                        Tab::make('Clinic Information')
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->schema([
                                Section::make('Clinic Information')
                                    ->description('Operational identity used across clinic workflows and verification reports.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('clinic_name')
                                                ->label('Clinic name')
                                                ->required()
                                                ->maxLength(255),
                                            Select::make('timezone')
                                                ->options(UsTimezoneOptions::options())
                                                ->searchable()
                                                ->preload()
                                                ->native(false)
                                                ->required(),
                                            Select::make('default_location_id')
                                                ->label('Default scheduling location')
                                                ->options(fn (): array => Location::query()
                                                    ->where('clinic_id', $this->clinic?->id)
                                                    ->where('status', true)
                                                    ->orderBy('location_name')
                                                    ->pluck('location_name', 'id')
                                                    ->all())
                                                ->helperText('Used automatically when a user is not assigned to a specific location.')
                                                ->searchable()
                                                ->preload(),
                                            TextInput::make('clinic_npi')
                                                ->label('Clinic NPI')
                                                ->helperText('The 10-digit clinic or organization NPI, separate from provider NPIs.')
                                                ->autocomplete('off')
                                                ->extraInputAttributes([
                                                    'data-1p-ignore' => 'true',
                                                    'data-lpignore' => 'true',
                                                ])
                                                ->length(10)
                                                ->regex('/^\d{10}$/'),
                                            TextInput::make('tax_id')
                                                ->label('Tax ID / EIN')
                                                ->autocomplete('off')
                                                ->extraInputAttributes([
                                                    'data-1p-ignore' => 'true',
                                                    'data-lpignore' => 'true',
                                                ])
                                                ->helperText('Encrypted at rest and visible only to authorized administrators.')
                                                ->maxLength(20),
                                            TextInput::make('website')
                                                ->url()
                                                ->placeholder('https://www.example.com')
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                        ]),
                                    ]),
                            ]),
                        Tab::make('Address & Contact')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Section::make('Clinic Contact')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('email')->email()->maxLength(255),
                                            TextInput::make('phone')->tel()->maxLength(30),
                                            TextInput::make('fax')->tel()->maxLength(30),
                                        ]),
                                    ]),
                                Section::make('Clinic Address')
                                    ->description('Use Locations for additional offices. This is the clinic profile address.')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('address')->maxLength(255)->columnSpanFull(),
                                            TextInput::make('city')->maxLength(100),
                                            TextInput::make('state')->maxLength(100),
                                            TextInput::make('zip_code')->label('ZIP / postal code')->maxLength(20),
                                            TextInput::make('country')->default('USA')->maxLength(100),
                                        ]),
                                    ]),
                                Section::make('Administrative Contacts')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('primary_contact_name')->label('Primary contact')->maxLength(255),
                                            TextInput::make('primary_contact_email')->label('Primary contact email')->email()->maxLength(255),
                                            TextInput::make('primary_contact_phone')->label('Primary contact phone')->tel()->maxLength(30),
                                            TextInput::make('billing_contact_name')->label('Billing contact')->maxLength(255),
                                            TextInput::make('billing_contact_email')->label('Billing contact email')->email()->maxLength(255),
                                            TextInput::make('billing_contact_phone')->label('Billing contact phone')->tel()->maxLength(30),
                                        ]),
                                    ]),
                            ]),
                        Tab::make('Business Hours')
                            ->icon(Heroicon::OutlinedClock)
                            ->schema($this->businessHoursSchema()),
                        Tab::make('Verification Contact')
                            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                            ->schema([
                                Section::make('Verification Contact')
                                    ->description('The operational contact used when verification questions require clinic input.')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('verification_contact_name')->label('Contact name')->maxLength(255),
                                            TextInput::make('verification_contact_email')->label('Contact email')->email()->maxLength(255),
                                            TextInput::make('verification_contact_phone')->label('Contact phone')->tel()->maxLength(30),
                                        ]),
                                    ]),
                            ]),
                        Tab::make('Branding & Notifications')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
                                Section::make('Report Branding')
                                    ->description('Applied to clinic-facing verification reports when available.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            FileUpload::make('logo_path')
                                                ->label('Clinic logo')
                                                ->disk('branding')
                                                ->directory('branding/clinics')
                                                ->image()
                                                ->imageEditor()
                                                ->maxSize(2048),
                                            TextInput::make('report_display_name')
                                                ->label('Report display name')
                                                ->maxLength(255),
                                            Textarea::make('report_footer')
                                                ->label('Report footer')
                                                ->rows(3)
                                                ->maxLength(500)
                                                ->columnSpanFull(),
                                        ]),
                                    ]),
                                Section::make('Notification Preferences')
                                    ->schema([
                                        TextInput::make('notification_email')
                                            ->label('Notification email')
                                            ->email()
                                            ->maxLength(255),
                                        Grid::make(3)->schema([
                                            Toggle::make('notification_preferences.verification_updates')
                                                ->label('Verification updates')
                                                ->default(true),
                                            Toggle::make('notification_preferences.clinic_responses')
                                                ->label('Clinic responses')
                                                ->default(true),
                                            Toggle::make('notification_preferences.appointment_updates')
                                                ->label('Appointment updates')
                                                ->default(true),
                                        ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function save(): void
    {
        abort_unless($this->canEditProfile(), 403);

        $clinic = $this->clinic ?? ClinicPanelScope::selectedClinic();
        abort_unless($clinic instanceof Clinic, 404);

        $before = $this->auditableProfile($clinic->toArray());
        $clinic->update($this->form->getState());

        if (auth()->user()?->shouldBypassClinicScope()) {
            SaasSupportAccess::recordModelEvent(
                'support_clinic_profile_updated',
                $clinic,
                $before,
                $this->auditableProfile($clinic->fresh()->toArray()),
            );
        }

        Notification::make()
            ->title('Clinic profile updated')
            ->body('The selected clinic profile has been saved successfully.')
            ->success()
            ->send();
    }

    public function canEditProfile(): bool
    {
        $user = auth()->user();
        $clinic = $this->clinic ?? ClinicPanelScope::selectedClinic();

        if (! $user || ! $clinic) {
            return false;
        }

        if ($user->shouldBypassClinicScope()) {
            return SaasSupportAccess::matchesScope((int) $clinic->organization_id, (int) $clinic->id);
        }

        return (int) $user->organization_id === (int) $clinic->organization_id
            && (int) $user->clinic_id === (int) $clinic->id
            && $user->hasAnyRole(['clinic_admin', 'clinic_manager']);
    }

    protected function businessHoursSchema(): array
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->map(fn (string $day): Section => Section::make(str($day)->headline()->toString())
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Toggle::make("business_hours.{$day}.open")
                            ->label('Open')
                            ->default(! in_array($day, ['saturday', 'sunday'], true)),
                        TimePicker::make("business_hours.{$day}.opens_at")
                            ->label('Opens at')
                            ->seconds(false),
                        TimePicker::make("business_hours.{$day}.closes_at")
                            ->label('Closes at')
                            ->seconds(false),
                    ]),
                ]))
            ->all();
    }

    protected function auditableProfile(array $values): array
    {
        $fields = [
            'clinic_name', 'clinic_npi', 'timezone', 'email', 'phone', 'fax', 'website',
            'address', 'city', 'state', 'zip_code', 'country', 'business_hours', 'default_location_id',
            'primary_contact_name', 'primary_contact_email', 'primary_contact_phone',
            'billing_contact_name', 'billing_contact_email', 'billing_contact_phone',
            'verification_contact_name', 'verification_contact_email', 'verification_contact_phone',
            'report_display_name', 'report_footer', 'notification_email', 'notification_preferences',
        ];

        return collect($values)->only($fields)->all();
    }
}
