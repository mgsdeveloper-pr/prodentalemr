<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use App\Support\VerificationReport;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class VerificationSlaAnalytics extends Widget
{
    protected string $view = 'filament.admin.widgets.verification-sla-analytics';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $query = AdminClinicScope::apply(
            BillingWorkItem::query()
                ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
                ->where('source', '!=', 'clinic_self_service'),
            'billing_work_items.clinic_id'
        );

        return [
            'analytics' => VerificationReport::slaAnalytics($query),
            'scopeLabel' => AdminClinicScope::selectedClinic()?->clinic_name ?: 'All verification clinics',
        ];
    }
}
