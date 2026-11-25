<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteCheckout extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['plan_snapshot' => 'array', 'amount_minor' => 'integer', 'paid_at' => 'datetime', 'initial_payment_confirmed_at' => 'datetime']; }
    public function registration() { return $this->belongsTo(WebsiteRegistration::class, 'website_registration_id'); }
    public function attempts() { return $this->hasMany(WebsitePaymentAttempt::class); }
}
