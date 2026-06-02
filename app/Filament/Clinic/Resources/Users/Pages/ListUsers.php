<?php

namespace App\Filament\Clinic\Resources\Users\Pages;

use App\Filament\Clinic\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => auth()->user()?->canManageClinicUsers() ?? false),
        ];
    }
}
