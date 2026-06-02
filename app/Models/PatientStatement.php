<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientStatement extends Model
{
    use SoftDeletes;

    public const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'issued' => 'Issued',
        'sent' => 'Sent',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'created_by',
        'statement_number',
        'statement_date',
        'period_from',
        'period_to',
        'status',
        'recipient_email',
        'sent_at',
        'last_sent_by',
        'opening_balance',
        'charges_total',
        'payments_total',
        'adjustments_total',
        'closing_balance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'sent_at' => 'datetime',
            'opening_balance' => 'decimal:2',
            'charges_total' => 'decimal:2',
            'payments_total' => 'decimal:2',
            'adjustments_total' => 'decimal:2',
            'closing_balance' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $statement): void {
            if (blank($statement->statement_number)) {
                $statement->statement_number = self::generateStatementNumber();
            }
        });
    }

    public static function generateStatementNumber(): string
    {
        $prefix = 'STM-' . now()->format('Ym') . '-';

        $latest = static::withTrashed()
            ->where('statement_number', 'like', "{$prefix}%")
            ->latest('id')
            ->value('statement_number');

        $sequence = 1;

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_sent_by');
    }

    public function ledgerEntries()
    {
        return PatientLedgerEntry::query()
            ->where('patient_id', $this->patient_id)
            ->where('organization_id', $this->organization_id)
            ->where('clinic_id', $this->clinic_id)
            ->whereBetween('posted_on', [$this->period_from, $this->period_to])
            ->where('status', '!=', 'void')
            ->orderBy('posted_on')
            ->orderBy('id');
    }

    public function refreshSummary(): void
    {
        $baseQuery = PatientLedgerEntry::query()
            ->where('patient_id', $this->patient_id)
            ->where('organization_id', $this->organization_id)
            ->where('clinic_id', $this->clinic_id)
            ->where('status', '!=', 'void');

        $openingBalance = (float) (clone $baseQuery)
            ->whereDate('posted_on', '<', $this->period_from)
            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) as balance')
            ->value('balance');

        $periodQuery = (clone $baseQuery)
            ->whereBetween('posted_on', [$this->period_from, $this->period_to]);

        $chargesTotal = (float) (clone $periodQuery)
            ->where('entry_type', 'charge')
            ->sum('debit_amount');

        $paymentsTotal = (float) (clone $periodQuery)
            ->whereIn('entry_type', ['patient_payment', 'insurance_payment'])
            ->sum('credit_amount');

        $adjustmentCredits = (float) (clone $periodQuery)
            ->whereIn('entry_type', ['adjustment', 'write_off', 'refund'])
            ->sum('credit_amount');

        $adjustmentDebits = (float) (clone $periodQuery)
            ->whereIn('entry_type', ['adjustment', 'write_off', 'refund'])
            ->sum('debit_amount');

        $adjustmentsTotal = $adjustmentCredits - $adjustmentDebits;

        $closingBalance = (float) (clone $periodQuery)
            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) as balance')
            ->value('balance');

        $this->opening_balance = round($openingBalance, 2);
        $this->charges_total = round($chargesTotal, 2);
        $this->payments_total = round($paymentsTotal, 2);
        $this->adjustments_total = round($adjustmentsTotal, 2);
        $this->closing_balance = round($openingBalance + $closingBalance, 2);

        $this->saveQuietly();
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' - ' . ($this->statement_number ?? 'Statement')),
        );
    }
}
