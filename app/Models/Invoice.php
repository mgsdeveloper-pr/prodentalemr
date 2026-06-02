<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'paid_at',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'notes',
        'stripe_checkout_session_id',
        'stripe_checkout_url',
        'stripe_checkout_expires_at',
        'stripe_payment_intent_id',
        'paypal_order_id',
        'paypal_approval_url',
        'paypal_capture_id',
        'paypal_order_status',
        'pre_due_reminder_sent_at',
        'overdue_reminder_sent_at',
        'marked_overdue_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'stripe_checkout_expires_at' => 'datetime',
            'pre_due_reminder_sent_at' => 'datetime',
            'overdue_reminder_sent_at' => 'datetime',
            'marked_overdue_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            if (blank($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $settings = SaasSetting::current();
        $style = $settings->invoice_number_style ?: 'prefixed_period_sequence';
        $digits = max((int) ($settings->invoice_number_digits ?? 4), 3);

        if ($style === 'random_alphanumeric') {
            do {
                $candidate = Str::upper(Str::random($digits));
            } while (static::withTrashed()->where('invoice_number', $candidate)->exists());

            return $candidate;
        }

        $separator = filled($settings->invoice_number_separator) ? $settings->invoice_number_separator : '-';
        $basePrefix = filled($settings->invoice_prefix) ? trim((string) $settings->invoice_prefix) : 'INV';
        $period = now()->format('Ym');

        if ($style === 'period_sequence') {
            $prefix = $period;
            $latest = static::withTrashed()
                ->where('invoice_number', 'like', "{$prefix}%")
                ->latest('id')
                ->value('invoice_number');
        } else {
            $segments = [$basePrefix];

            if ($settings->invoice_include_period_prefix ?? true) {
                $segments[] = $period;
            }

            $prefix = implode($separator, array_filter($segments, fn (?string $segment): bool => filled($segment))) . $separator;
            $latest = static::withTrashed()
                ->where('invoice_number', 'like', "{$prefix}%")
                ->latest('id')
                ->value('invoice_number');
        }

        $sequence = 1;

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refreshFinancialSummary(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $amountPaid = (float) $this->payments()->sum('amount');
        $totalAmount = max($subtotal + (float) $this->tax_amount - (float) $this->discount_amount, 0);
        $balanceDue = max($totalAmount - $amountPaid, 0);

        $this->subtotal = $subtotal;
        $this->total_amount = $totalAmount;
        $this->amount_paid = $amountPaid;
        $this->balance_due = $balanceDue;

        if ($balanceDue <= 0 && $totalAmount > 0) {
            $this->status = 'paid';
            $this->paid_at = $this->payments()->latest('payment_date')->value('payment_date') ?? now()->toDateString();
        } elseif ($amountPaid > 0) {
            $this->status = 'partial';
            $this->paid_at = null;
        } elseif ($this->status === 'paid') {
            $this->status = 'sent';
            $this->paid_at = null;
        }

        $this->saveQuietly();
    }

    public function refreshPaymentSummary(): void
    {
        $this->refreshFinancialSummary();
    }
}
