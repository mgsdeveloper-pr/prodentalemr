<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\AccountWidget;

class AdminAccountWidget extends AccountWidget
{
    protected static bool $isLazy = true;

    protected string $view = 'filament.admin.widgets.account-widget';
}
