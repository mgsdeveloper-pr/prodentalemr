<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EligibilityConnection extends Model
{
    protected $fillable = ['provider', 'environment', 'api_key', 'enabled', 'updated_by', 'last_checked_at', 'last_check_status'];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'enabled' => 'boolean', 'eligibility_enabled' => 'boolean', 'last_checked_at' => 'datetime'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(EligibilityConnectionEvent::class);
    }
}
