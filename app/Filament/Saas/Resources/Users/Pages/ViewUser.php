<?php

namespace App\Filament\Saas\Resources\Users\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
