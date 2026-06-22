<?php

namespace App\Filament\Saas\Pages;

use App\Models\SaasSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BillingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Billing Settings';

    protected static ?int $navigationSort = 25;

    protected static ?string $title = 'Billing Settings';

    protected static ?string $slug = 'billing-settings';

    protected string $view = 'filament.saas.pages.billing-settings';

    public ?array $data = [];

    protected SaasSetting $settings;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('settings') ?? false;
    }

    public function mount(): void
    {
        $this->settings = SaasSetting::current();

        $this->form->fill([
            ...$this->settings->toArray(),
            'quickbooks_client_secret' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Billing Configuration')
                    ->persistTabInQueryString('billing-tab')
                    ->tabs([
                        Tab::make('Invoice Settings')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        FileUpload::make('invoice_logo_path')
                                            ->label('Invoice logo')
                                            ->disk('branding')
                                            ->directory('branding/invoices')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->helperText('Used on invoice PDFs. Falls back to the platform logo if left blank.'),
                                        FileUpload::make('invoice_signature_path')
                                            ->label('Authorised signatory signature')
                                            ->disk('branding')
                                            ->directory('branding/invoices')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->helperText('Optional. Only shown when authorised signatory display is enabled.'),
                                        Select::make('invoice_language')
                                            ->label('Language')
                                            ->options([
                                                'en' => 'English',
                                            ])
                                            ->default('en')
                                            ->required()
                                            ->native(false),
                                        TextInput::make('invoice_due_after_days')
                                            ->label('Due after')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(3)
                                            ->suffix('day(s)')
                                            ->required(),
                                        TextInput::make('billing_pre_due_days')
                                            ->label('Send reminder before')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(3)
                                            ->suffix('day(s)')
                                            ->required(),
                                        TextInput::make('billing_overdue_reminder_days')
                                            ->label('Send reminder after')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(1)
                                            ->suffix('day(s)')
                                            ->required(),
                                        TextInput::make('invoice_tax_number_value')
                                            ->label('Tax / EIN number')
                                            ->maxLength(255),
                                        Textarea::make('invoice_tax_message_text')
                                            ->label('Tax calculation message')
                                            ->rows(3)
                                            ->helperText('Optional explanatory tax message shown on invoice when enabled.'),
                                    ]),
                                Grid::make(3)
                                    ->schema([
                                        Toggle::make('billing_automation_enabled')
                                            ->label('Enable billing automation')
                                            ->default(false),
                                        Toggle::make('billing_mark_overdue_enabled')
                                            ->label('Auto mark overdue')
                                            ->default(true),
                                        Toggle::make('billing_send_pre_due_reminders')
                                            ->label('Pre-due reminders')
                                            ->default(true),
                                        Toggle::make('billing_send_overdue_reminders')
                                            ->label('Post-due reminders')
                                            ->default(true),
                                        Toggle::make('invoice_show_tax_number')
                                            ->label('Show tax number on invoice')
                                            ->default(false),
                                        Toggle::make('invoice_show_status')
                                            ->label('Show payment status')
                                            ->default(true),
                                        Toggle::make('invoice_show_tax_message')
                                            ->label('Show tax calculation message')
                                            ->default(false),
                                        Toggle::make('invoice_show_authorised_signatory')
                                            ->label('Show authorised signatory')
                                            ->default(false),
                                    ]),
                                Section::make('Client Info To Show On Invoice')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                Toggle::make('invoice_show_client_name')
                                                    ->label('Client name')
                                                    ->default(true),
                                                Toggle::make('invoice_show_client_email')
                                                    ->label('Client email')
                                                    ->default(true),
                                                Toggle::make('invoice_show_client_phone')
                                                    ->label('Client phone')
                                                    ->default(true),
                                                Toggle::make('invoice_show_client_address')
                                                    ->label('Client address')
                                                    ->default(true),
                                            ]),
                                    ]),
                                Textarea::make('invoice_terms_conditions')
                                    ->label('Terms and Conditions')
                                    ->rows(4)
                                    ->helperText('Optional reusable terms for invoices.'),
                            ]),
                        Tab::make('Invoice Template')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('invoice_template_style')
                                            ->label('Template style')
                                            ->options([
                                                'modern' => 'Modern',
                                                'classic' => 'Classic',
                                                'minimal' => 'Minimal',
                                            ])
                                            ->default('modern')
                                            ->required()
                                            ->native(false),
                                        Toggle::make('invoice_compact_layout')
                                            ->label('Use compact single-page layout')
                                            ->default(true),
                                        Toggle::make('invoice_show_payment_instructions')
                                            ->label('Show payment instructions block')
                                            ->default(true),
                                        Toggle::make('invoice_show_invoice_notice')
                                            ->label('Show invoice notice block')
                                            ->default(true),
                                    ]),
                            ]),
                        Tab::make('Prefix Settings')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('invoice_number_style')
                                            ->label('Invoice number format')
                                            ->options([
                                                'prefixed_period_sequence' => 'INV-YYYYMM-0001',
                                                'period_sequence' => 'YYYYMM0001',
                                                'random_alphanumeric' => 'Random Alphanumeric',
                                            ])
                                            ->default('prefixed_period_sequence')
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->columnSpan(2),
                                        TextInput::make('invoice_prefix')
                                            ->label('Invoice prefix')
                                            ->default('INV')
                                            ->maxLength(20)
                                            ->visible(fn (Get $get): bool => ($get('invoice_number_style') ?? 'prefixed_period_sequence') === 'prefixed_period_sequence')
                                            ->required(fn (Get $get): bool => ($get('invoice_number_style') ?? 'prefixed_period_sequence') === 'prefixed_period_sequence'),
                                        TextInput::make('invoice_number_separator')
                                            ->label('Separator')
                                            ->default('-')
                                            ->maxLength(5)
                                            ->visible(fn (Get $get): bool => ($get('invoice_number_style') ?? 'prefixed_period_sequence') === 'prefixed_period_sequence')
                                            ->required(fn (Get $get): bool => ($get('invoice_number_style') ?? 'prefixed_period_sequence') === 'prefixed_period_sequence'),
                                        TextInput::make('invoice_number_digits')
                                            ->label(fn (Get $get): string => ($get('invoice_number_style') ?? 'prefixed_period_sequence') === 'random_alphanumeric'
                                                ? 'Random code length'
                                                : 'Number digits')
                                            ->numeric()
                                            ->minValue(3)
                                            ->maxValue(12)
                                            ->default(4)
                                            ->helperText(fn (Get $get): ?string => ($get('invoice_number_style') ?? 'prefixed_period_sequence') === 'random_alphanumeric'
                                                ? 'Used as the length of the auto-generated uppercase invoice code.'
                                                : null)
                                            ->required(),
                                        Toggle::make('invoice_include_period_prefix')
                                            ->label('Include year/month in number')
                                            ->visible(fn (Get $get): bool => ($get('invoice_number_style') ?? 'prefixed_period_sequence') === 'prefixed_period_sequence')
                                            ->default(true),
                                    ]),
                            ]),
                        Tab::make('Units')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('invoice_unit_label')
                                            ->label('Quantity column label')
                                            ->default('Qty')
                                            ->maxLength(20)
                                            ->required(),
                                        TextInput::make('invoice_quantity_precision')
                                            ->label('Quantity decimal places')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(4)
                                            ->default(2)
                                            ->required(),
                                    ]),
                            ]),
                        Tab::make('QuickBooks Settings')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Toggle::make('quickbooks_enabled')
                                            ->label('Enable QuickBooks settings')
                                            ->default(false)
                                            ->live(),
                                        Toggle::make('quickbooks_auto_sync')
                                            ->label('Auto sync billing records')
                                            ->default(false)
                                            ->visible(fn (Get $get): bool => (bool) $get('quickbooks_enabled')),
                                        TextInput::make('quickbooks_company_id')
                                            ->label('Company / Realm ID')
                                            ->maxLength(255)
                                            ->visible(fn (Get $get): bool => (bool) $get('quickbooks_enabled')),
                                        TextInput::make('quickbooks_client_id')
                                            ->label('Client ID')
                                            ->maxLength(255)
                                            ->visible(fn (Get $get): bool => (bool) $get('quickbooks_enabled')),
                                        TextInput::make('quickbooks_client_secret')
                                            ->label('Client secret')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Leave blank to keep the currently saved secret.')
                                            ->maxLength(65535)
                                            ->visible(fn (Get $get): bool => (bool) $get('quickbooks_enabled'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Invoice Payment Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('bank_account_name')
                                            ->label('Account holder name')
                                            ->maxLength(255),
                                        Select::make('bank_account_type')
                                            ->label('Account type')
                                            ->options([
                                                'checking' => 'Checking',
                                                'savings' => 'Savings',
                                            ])
                                            ->native(false),
                                        TextInput::make('bank_name')
                                            ->label('Bank name')
                                            ->maxLength(255),
                                        TextInput::make('bank_account_number')
                                            ->label('Account number')
                                            ->maxLength(255),
                                        TextInput::make('bank_routing_number')
                                            ->label('ABA routing number')
                                            ->maxLength(255),
                                        TextInput::make('bank_swift_code')
                                            ->label('SWIFT / BIC code')
                                            ->helperText('Optional. Use only if you also accept international wire transfers.')
                                            ->maxLength(255),
                                        TextInput::make('bank_branch')
                                            ->label('Bank branch or address')
                                            ->maxLength(255),
                                        Textarea::make('bank_payment_notes')
                                            ->label('Bank payment notes')
                                            ->rows(4)
                                            ->helperText('Example: ACH only, wire instructions, beneficiary notes, or remittance guidance.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save billing settings')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $settings = SaasSetting::current();
        $data = $this->form->getState();

        $persistedQuickBooksSecret = $settings->quickbooks_client_secret;
        $newQuickBooksSecret = $data['quickbooks_client_secret'] ?? null;

        unset($data['quickbooks_client_secret']);

        if ((bool) ($data['quickbooks_enabled'] ?? false)) {
            if (filled($newQuickBooksSecret)) {
                $data['quickbooks_client_secret'] = $newQuickBooksSecret;
            } elseif (filled($persistedQuickBooksSecret)) {
                $data['quickbooks_client_secret'] = $persistedQuickBooksSecret;
            }
        } else {
            $data['quickbooks_auto_sync'] = false;
            $data['quickbooks_company_id'] = null;
            $data['quickbooks_client_id'] = null;
            $data['quickbooks_client_secret'] = null;
        }

        $settings->update($data);

        Notification::make()
            ->title('Billing settings saved')
            ->body('Invoice, numbering, reminder, template, and payment settings have been updated.')
            ->success()
            ->send();
    }
}
