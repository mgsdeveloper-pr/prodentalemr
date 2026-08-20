@php
    $supportAccess = \App\Support\SaasSupportAccess::active();
    $auditUrl = \App\Filament\Saas\Resources\SaasEntitlementAuditLogs\SaasEntitlementAuditLogResource::getUrl('index', [
        'tableFilters[event_type][value]' => 'support_access_started',
    ]);
    $organizationUrl = filled($supportAccess['organization_id'] ?? null)
        ? \App\Filament\Saas\Pages\OrganizationWorkspace::getUrl([
            'record' => $supportAccess['organization_id'],
        ])
        : null;
@endphp

@if ($supportAccess)
    <div style="position: sticky; top: 0; z-index: 40; border-bottom: 1px solid #fed7aa; background: #fff7ed; color: #7c2d12; padding: 10px 20px; font-size: 13px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <strong style="font-size: 13px;">Support Mode Active</strong>
                <span>{{ $supportAccess['organization_name'] ?? 'Client' }}</span>
                @if (filled($supportAccess['clinic_name'] ?? null))
                    <span>/ {{ $supportAccess['clinic_name'] }}</span>
                @endif
                <span style="color: #9a3412;">Reason: {{ $supportAccess['reason'] ?? '-' }}</span>
                <span style="color: #9a3412;">Started: {{ filled($supportAccess['started_at'] ?? null) ? \Illuminate\Support\Carbon::parse($supportAccess['started_at'])->format('M d, h:i A') : '-' }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                @if ($organizationUrl)
                    <a href="{{ $organizationUrl }}" style="color: #7c2d12; font-weight: 800; text-decoration: underline;">Client Workspace</a>
                @endif
                <a href="{{ $auditUrl }}" style="color: #7c2d12; font-weight: 800; text-decoration: underline;">Audit Trail</a>
            </div>
        </div>
    </div>
@endif
