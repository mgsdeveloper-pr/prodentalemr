<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'service_item_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        $refreshInvoice = function (self $item): void {
            $invoice = $item->invoice()->withTrashed()->first();

            if ($invoice) {
                $invoice->refreshFinancialSummary();
            }
        };

        static::saved($refreshInvoice);
        static::deleted($refreshInvoice);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class);
    }
}
