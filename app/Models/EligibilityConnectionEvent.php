<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibilityConnectionEvent extends Model
{
    protected $fillable = ['eligibility_connection_id', 'user_id', 'event', 'status'];
}
