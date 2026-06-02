<?php

namespace App\Filament\Clinic\Widgets;

use Filament\Widgets\AccountWidget;

class ClinicAccountWidget extends AccountWidget
{
    protected static bool $isLazy = true;

    protected string $view = 'filament.clinic.widgets.account-widget';
}
