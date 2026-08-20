<?php

namespace App\Models;

/**
 * Domain-facing alias for the existing verification request storage.
 *
 * The current table remains `billing_work_items` for backward compatibility.
 */
class VerificationRequest extends BillingWorkItem
{
    protected $table = 'billing_work_items';
}
