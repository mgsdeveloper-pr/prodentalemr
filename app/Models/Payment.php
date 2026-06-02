<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    public const METHOD_LABELS = [
        'manual' => 'Manual',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Card',
        'check' => 'Check',
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'other' => 'Other',
    ];

    protected $fillable = [
        'invoice_id',
        'organization_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        $refreshInvoice = function (self $payment): void {
            $invoice = $payment->invoice()->withTrashed()->first();

            if ($invoice) {
                $invoice->refreshPaymentSummary();
            }
        };

        static::saved($refreshInvoice);
        static::deleted($refreshInvoice);
        static::restored($refreshInvoice);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function methodOptions(): array
    {
        return self::METHOD_LABELS;
    }
}
