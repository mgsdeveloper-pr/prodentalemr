<?php

namespace App\Filament\Clinic\Resources\Users\Pages;

use App\Filament\Clinic\Resources\Users\UserResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => UserResource::canEdit($this->record)),
        ];
    }
}
