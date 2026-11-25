<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePaymentAttempt extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['last_checked_at' => 'datetime', 'capture_started_at' => 'datetime']; }
    public function checkout() { return $this->belongsTo(WebsiteCheckout::class, 'website_checkout_id'); }
}
