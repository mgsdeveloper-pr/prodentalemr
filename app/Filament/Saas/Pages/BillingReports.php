<?php

namespace App\Filament\Saas\Pages;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Support\BillingExport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class BillingReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Billing Reports';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Billing Reports';

    protected static ?string $slug = 'billing-reports';

    protected string $view = 'filament.saas.pages.billing-reports';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('invoices') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'organization_id' => null,
            'invoice_status' => null,
            'payment_method' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Report Filters')
                    ->description('Filter billing performance, invoice status, and payment collections for the current reporting view.')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                DatePicker::make('from_date')
                                    ->label('From date'),
                                DatePicker::make('to_date')
                                    ->label('To date'),
                                Select::make('organization_id')
                                    ->label('Organization')
                                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('invoice_status')
                                    ->label('Invoice status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'partial' => 'Partial',
                                        'paid' => 'Paid',
                                        'overdue' => 'Overdue',
                                        'cancelled' => 'Cancelled',
                                    ]),
                                Select::make('payment_method')
                                    ->label('Payment method')
                                    ->options(Payment::methodOptions()),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetFilters')
                ->label('Reset filters')
                ->action(function (): void {
                    $this->form->fill([
                        'from_date' => now()->startOfMonth()->toDateString(),
                        'to_date' => now()->toDateString(),
                        'organization_id' => null,
                        'invoice_status' => null,
                        'payment_method' => null,
                    ]);
                }),
            Action::make('exportInvoices')
                ->label('Export invoices CSV')
                ->action(fn () => response()->streamDownload(
                    fn () => print(BillingExport::invoicesCsv($this->invoiceQueryForExport())),
                    'billing-report-invoices.csv',
                    ['Content-Type' => 'text/csv'],
                )),
            Action::make('exportPayments')
                ->label('Export payments CSV')
                ->action(fn () => response()->streamDownload(
                    fn () => print(BillingExport::paymentsCsv($this->paymentQueryForExport())),
                    'billing-report-payments.csv',
                    ['Content-Type' => 'text/csv'],
                )),
        ];
    }

    public function getSummaryCards(): array
    {
        $invoiceQuery = $this->invoiceQuery();
        $paymentQuery = $this->paymentQuery();

        return [
            [
                'label' => 'Invoiced total',
                'value' => '$' . number_format((float) (clone $invoiceQuery)->sum('total_amount'), 2),
                'description' => 'Total billed in the selected range',
            ],
            [
                'label' => 'Collected total',
                'value' => '$' . number_format((float) (clone $paymentQuery)->sum('amount'), 2),
                'description' => 'Payments collected in the selected range',
            ],
            [
                'label' => 'Outstanding balance',
                'value' => '$' . number_format((float) (clone $invoiceQuery)->sum('balance_due'), 2),
                'description' => 'Remaining balance on filtered invoices',
            ],
            [
                'label' => 'Invoice count',
                'value' => number_format((clone $invoiceQuery)->count()),
                'description' => 'Invoices matching current filters',
            ],
        ];
    }

    public function getInvoiceStatusBreakdown(): array
    {
        return (clone $this->invoiceQuery())
            ->selectRaw('status, COUNT(*) as total, SUM(total_amount) as amount')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->status,
                'total' => (int) $row->total,
                'amount' => (float) $row->amount,
            ])
            ->all();
    }

    public function getPaymentMethodBreakdown(): array
    {
        return (clone $this->paymentQuery())
            ->selectRaw('payment_method, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get()
            ->map(fn ($row): array => [
                'method' => Payment::methodOptions()[$row->payment_method] ?? (string) $row->payment_method,
                'total' => (int) $row->total,
                'amount' => (float) $row->amount,
            ])
            ->all();
    }

    public function getRecentInvoices(): array
    {
        return (clone $this->invoiceQuery())
            ->with('organization')
            ->latest('issue_date')
            ->limit(8)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'invoice_number' => $invoice->invoice_number,
                'organization' => $invoice->organization?->name,
                'status' => $invoice->status,
                'issue_date' => optional($invoice->issue_date)?->format('Y-m-d'),
                'total_amount' => (float) $invoice->total_amount,
                'balance_due' => (float) $invoice->balance_due,
            ])
            ->all();
    }

    public function getRecentPayments(): array
    {
        return (clone $this->paymentQuery())
            ->with(['organization', 'invoice'])
            ->latest('payment_date')
            ->limit(8)
            ->get()
            ->map(fn (Payment $payment): array => [
                'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
                'invoice_number' => $payment->invoice?->invoice_number,
                'organization' => $payment->organization?->name,
                'method' => Payment::methodOptions()[$payment->payment_method] ?? $payment->payment_method,
                'amount' => (float) $payment->amount,
            ])
            ->all();
    }

    public function getTrendChart(): array
    {
        $filters = $this->form->getState();
        $from = filled($filters['from_date'] ?? null) ? Carbon::parse($filters['from_date'])->startOfDay() : now()->startOfMonth();
        $to = filled($filters['to_date'] ?? null) ? Carbon::parse($filters['to_date'])->startOfDay() : now()->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $period = Collection::times($from->diffInDays($to) + 1, fn (int $index): Carbon => $from->copy()->addDays($index - 1));

        $invoiceTotals = (clone $this->invoiceQuery())
            ->selectRaw('DATE(issue_date) as trend_date, SUM(total_amount) as total')
            ->groupBy(DB::raw('DATE(issue_date)'))
            ->pluck('total', 'trend_date');

        $paymentTotals = (clone $this->paymentQuery())
            ->selectRaw('DATE(payment_date) as trend_date, SUM(amount) as total')
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->pluck('total', 'trend_date');

        $labels = [];
        $invoiced = [];
        $collected = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');

            $labels[] = $date->format('M j');
            $invoiced[] = (float) ($invoiceTotals[$key] ?? 0);
            $collected[] = (float) ($paymentTotals[$key] ?? 0);
        }

        $max = max(max($invoiced ?: [0]), max($collected ?: [0]), 1);

        return [
            'labels' => $labels,
            'invoiced' => $invoiced,
            'collected' => $collected,
            'invoiced_points' => $this->buildPolylinePoints($invoiced, $max),
            'collected_points' => $this->buildPolylinePoints($collected, $max),
            'max' => $max,
            'total_invoiced' => array_sum($invoiced),
            'total_collected' => array_sum($collected),
        ];
    }

    public function getInvoiceStatusVisualization(): array
    {
        $rows = $this->getInvoiceStatusBreakdown();
        $max = max(array_column($rows, 'total') ?: [1]);

        return array_map(function (array $row) use ($max): array {
            $row['width'] = $max > 0 ? max(($row['total'] / $max) * 100, 6) : 0;

            return $row;
        }, $rows);
    }

    public function getPaymentMethodVisualization(): array
    {
        $rows = $this->getPaymentMethodBreakdown();
        $max = max(array_column($rows, 'amount') ?: [1]);

        return array_map(function (array $row) use ($max): array {
            $row['width'] = $max > 0 ? max(($row['amount'] / $max) * 100, 6) : 0;

            return $row;
        }, $rows);
    }

    protected function invoiceQuery(): Builder
    {
        $filters = $this->form->getState();

        return Invoice::query()
            ->with('organization')
            ->when($filters['from_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('issue_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('issue_date', '<=', $date))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, $organizationId) => $query->where('organization_id', $organizationId))
            ->when($filters['invoice_status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status));
    }

    protected function paymentQuery(): Builder
    {
        $filters = $this->form->getState();

        return Payment::query()
            ->with(['organization', 'invoice'])
            ->when($filters['from_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('payment_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('payment_date', '<=', $date))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, $organizationId) => $query->where('organization_id', $organizationId))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, $method) => $query->where('payment_method', $method));
    }

    protected function invoiceQueryForExport(): Builder
    {
        return $this->invoiceQuery()->latest('issue_date');
    }

    protected function paymentQueryForExport(): Builder
    {
        return $this->paymentQuery()->latest('payment_date');
    }

    protected function buildPolylinePoints(array $values, float $maxValue): string
    {
        $width = 640;
        $height = 220;
        $padding = 20;
        $count = count($values);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            $x = $padding;
            $y = $height - $padding;

            return "{$x},{$y}";
        }

        return collect($values)
            ->map(function (float $value, int $index) use ($count, $width, $height, $padding, $maxValue): string {
                $x = $padding + (($width - ($padding * 2)) / max($count - 1, 1)) * $index;
                $ratio = $maxValue > 0 ? ($value / $maxValue) : 0;
                $y = $height - $padding - (($height - ($padding * 2)) * $ratio);

                return round($x, 2) . ',' . round($y, 2);
            })
            ->implode(' ');
    }
}
