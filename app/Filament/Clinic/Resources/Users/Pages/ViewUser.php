<?php

namespace App\Filament\Clinic\Resources\Users\Pages;

use App\Filament\Clinic\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canManageClinicUsers() ?? false),
        ];
    }
}
