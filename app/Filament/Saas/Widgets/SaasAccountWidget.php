<?php

namespace App\Filament\Saas\Widgets;

use Filament\Widgets\AccountWidget;

class SaasAccountWidget extends AccountWidget
{
    protected static bool $isLazy = true;

    protected string $view = 'filament.saas.widgets.account-widget';
}
