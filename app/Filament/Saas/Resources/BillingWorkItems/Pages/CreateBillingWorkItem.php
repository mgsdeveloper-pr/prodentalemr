<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\Pages;

use App\Filament\Saas\Resources\BillingWorkItems\BillingWorkItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingWorkItem extends CreateRecord
{
    protected static string $resource = BillingWorkItemResource::class;
}
