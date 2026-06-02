<?php

namespace App\Filament\Admin\Pages;

use App\Models\VerificationNotification;
use App\Support\AdminClinicScope;
use App\Support\VerificationNotificationCenter;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class VerificationNotificationCentre extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?string $navigationLabel = 'Notification Centre';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Verification Notification Centre';

    protected static ?string $slug = 'verification-notifications';

    protected string $view = 'filament.shared.verification-notification-centre';

    public string $search = '';
    public string $readFilter = 'all';
    public ?string $typeFilter = null;
    public ?string $fromDate = null;
    public ?string $toDate = null;

    public static function canAccess(): bool
    {
        return (bool) (
            auth()->user()?->canAccessVerificationModule('notifications')
            || auth()->user()?->canAccessSaasRevenueOperations()
        );
    }

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        VerificationNotificationCenter::syncSlaAlertsForUser(auth()->user(), 'verification', AdminClinicScope::selectedClinicId());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = VerificationNotification::query()
            ->where('user_id', auth()->id())
            ->where('panel', 'verification')
            ->whereNull('read_at')
            ->when(filled(AdminClinicScope::selectedClinicId()), fn (Builder $query) => $query->where('clinic_id', AdminClinicScope::selectedClinicId()))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getNotifications(): Collection
    {
        return $this->query()
            ->orderByRaw("case when level = 'danger' and read_at is null then 0 when read_at is null then 1 else 2 end")
            ->latest('created_at')
            ->limit(50)
            ->get();
    }

    public function getSummary(): array
    {
        $query = $this->query();

        return [
            'total' => (clone $query)->count(),
            'unread' => (clone $query)->whereNull('read_at')->count(),
            'today' => (clone $query)->whereDate('created_at', now()->toDateString())->count(),
            'critical' => (clone $query)->whereIn('level', ['danger', 'warning'])->count(),
        ];
    }

    public function notificationTypeOptions(): array
    {
        return $this->query()
            ->select('activity_type')
            ->distinct()
            ->whereNotNull('activity_type')
            ->orderBy('activity_type')
            ->pluck('activity_type', 'activity_type')
            ->mapWithKeys(fn ($label, $value) => [$value => str($label)->replace('_', ' ')->title()->toString()])
            ->all();
    }

    public function markAsRead(int $notificationId): void
    {
        $this->query()->whereKey($notificationId)->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        $this->query()->whereNull('read_at')->update(['read_at' => now()]);
    }

    protected function query(): Builder
    {
        return VerificationNotification::query()
            ->where('user_id', auth()->id())
            ->where('panel', 'verification')
            ->when(filled(AdminClinicScope::selectedClinicId()), fn (Builder $query) => $query->where('clinic_id', AdminClinicScope::selectedClinicId()))
            ->when(filled($this->search), fn (Builder $query) => $query->where(function (Builder $builder): void {
                $builder->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('message', 'like', '%' . $this->search . '%');
            }))
            ->when($this->readFilter === 'unread', fn (Builder $query) => $query->whereNull('read_at'))
            ->when($this->readFilter === 'read', fn (Builder $query) => $query->whereNotNull('read_at'))
            ->when(filled($this->typeFilter), fn (Builder $query) => $query->where('activity_type', $this->typeFilter))
            ->when(filled($this->fromDate), fn (Builder $query) => $query->whereDate('created_at', '>=', $this->fromDate))
            ->when(filled($this->toDate), fn (Builder $query) => $query->whereDate('created_at', '<=', $this->toDate));
    }
}
