<?php

namespace App\Filament\Saas\Resources\Providers\Pages;

use App\Filament\Saas\Resources\Providers\ProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;
}
