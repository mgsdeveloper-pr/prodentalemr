@php
    $organization = $this->getOrganization();
    $clinic = $this->getClinic();
    $metrics = $this->getWorkspaceMetrics();
    $links = $this->getWorkspaceLinks();
    $recentVerifications = $this->getRecentVerificationRows();
    $activity = $this->getRecentActivityRows();
    $readiness = collect($this->getWorkspaceReadiness());

    $readinessReady = $readiness->where('status', 'ready')->count();
    $readinessTotal = $readiness->count();
    $readinessPercent = $readinessTotal > 0 ? (int) round(($readinessReady / $readinessTotal) * 100) : 0;
    $organizationCode = data_get($organization, 'public_id')
        ?? data_get($organization, 'uuid')
        ?? ($organization ? 'ORG-' . str_pad((string) $organization->id, 8, '0', STR_PAD_LEFT) : 'ORG-UNASSIGNED');

    $leftRows = [
        ['label' => 'Organization Summary', 'icon' => 'squares-2x2', 'value' => null, 'active' => true],
        ['label' => 'Clinics', 'icon' => 'building-storefront', 'value' => number_format($metrics['clinic_count']), 'active' => false],
        ['label' => 'Users', 'icon' => 'users', 'value' => number_format($metrics['active_user_count']), 'active' => false],
        ['label' => 'Verification Config', 'icon' => 'cog-6-tooth', 'value' => $clinic?->hasActiveVerificationServices() ? 'ok' : null, 'active' => false],
        ['label' => 'Recent Activity', 'icon' => 'clock', 'value' => $activity->count() ?: null, 'active' => false],
        ['label' => 'Documents', 'icon' => 'document-text', 'value' => number_format($metrics['verification_document_count']), 'active' => false],
        ['label' => 'Workspace Readiness', 'icon' => 'shield-check', 'value' => $readinessPercent . '%', 'active' => false],
    ];

    $metricCards = [
        ['label' => 'Clinics', 'value' => number_format($metrics['clinic_count']), 'hint' => 'Active clinics', 'icon' => 'building-office-2', 'tone' => 'blue'],
        ['label' => 'Users', 'value' => number_format($metrics['active_user_count']), 'hint' => 'Active users', 'icon' => 'users', 'tone' => 'green'],
        ['label' => 'Verification Requests', 'value' => number_format($metrics['open_verification_count']), 'hint' => 'Open requests', 'icon' => 'clipboard-document-check', 'tone' => 'purple'],
        ['label' => 'Completed', 'value' => number_format($metrics['completed_this_month']), 'hint' => 'This month', 'icon' => 'check', 'tone' => 'emerald'],
    ];

    $quickActions = collect([
        ['key' => 'users', 'label' => 'Add User', 'icon' => 'user-plus'],
        ['key' => 'document_center', 'label' => 'Upload Document', 'icon' => 'document-plus'],
        ['key' => 'portal_credentials', 'label' => 'Manage Portal Credentials', 'icon' => 'link'],
        ['key' => 'settings', 'label' => 'Verification Config', 'icon' => 'cog-6-tooth'],
    ])->filter(fn (array $action): bool => ($links[$action['key']]['visible'] ?? false) === true);

    $configurationRows = [
        ['label' => 'Verification Templates', 'value' => number_format($metrics['template_question_count']), 'helper' => 'Active template questions', 'status' => ($metrics['template_question_count'] ?? 0) > 0 ? 'ready' : 'attention'],
        ['label' => 'Portal Credentials', 'value' => number_format($metrics['portal_credential_count']), 'helper' => 'Active clinic credentials', 'status' => ($metrics['portal_credential_count'] ?? 0) > 0 ? 'ready' : 'attention'],
        ['label' => 'Unread Notifications', 'value' => number_format($metrics['unread_notification_count']), 'helper' => 'Current user notifications', 'status' => ($metrics['unread_notification_count'] ?? 0) > 0 ? 'attention' : 'ready'],
    ];
@endphp

<x-filament-panels::page>
    <div class="pds-organization-workspace pwdl-workspace -mx-4 -mt-6 bg-slate-50 text-slate-950 sm:-mx-6 lg:-mx-8">
        <style>
            .pds-organization-workspace {
                --workspace-border: var(--pwdl-border-subtle, #e2e8f0);
                --workspace-muted: var(--pwdl-text-muted, #64748b);
                --workspace-panel: var(--pwdl-surface-card, #ffffff);
            }

            .pds-organization-card {
                border: 1px solid var(--workspace-border);
                background: var(--workspace-panel);
                border-radius: var(--pwdl-radius-xl, 14px);
                box-shadow: var(--pwdl-shadow-card, 0 12px 30px rgba(15, 23, 42, 0.045));
            }

            .pds-organization-soft {
                background: linear-gradient(135deg, #ecfdf5 0%, #f8fafc 100%);
                border-color: #a7f3d0;
            }

            .pds-readiness-ring {
                background: conic-gradient(#059669 calc(var(--progress) * 1%), #e2e8f0 0);
            }
        </style>

        <section class="border-b border-slate-200 bg-white px-6 py-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                        <x-heroicon-o-building-office-2 class="h-7 w-7" />
                    </span>
                    <div class="min-w-0">
                        <h1 class="truncate text-2xl font-bold tracking-normal text-slate-950">Organization Operations Workspace</h1>
                        <p class="mt-1 text-sm text-slate-600">Manage your organization and configure verification operations</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />
                        Focus Mode
                    </button>
                    <button type="button" aria-label="Workspace options" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <x-heroicon-o-ellipsis-horizontal class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </section>

        @unless ($organization)
            <div class="p-6">
                <x-pds.empty-state
                    title="Organization context unavailable"
                    description="The current user does not have an organization assigned for this workspace."
                />
            </div>
        @else
            <section class="pwdl-three-column grid grid-cols-1 gap-5 p-6 2xl:grid-cols-[300px_minmax(0,1fr)_320px]">
                <aside class="min-w-0">
                    <div class="pds-organization-card 2xl:sticky 2xl:top-5">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-rectangle-stack class="h-4 w-4 text-slate-500" />
                                <h2 class="text-sm font-bold text-slate-950">Organization Context</h2>
                            </div>
                            <button type="button" aria-label="Collapse organization context" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500">
                                <x-heroicon-o-chevron-up class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="space-y-3 p-3">
                            <div class="pds-organization-card pds-organization-soft p-4 shadow-none">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                        <x-heroicon-o-building-office-2 class="h-6 w-6" />
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-bold text-slate-950">{{ $organization->name }}</h3>
                                        <span class="mt-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                            {{ $organization->status ? 'Active' : 'Inactive' }}
                                        </span>
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $organizationCode }}</p>
                                        @if ($clinic?->clinic_name)
                                            <p class="mt-1 truncate text-xs font-semibold text-slate-700">{{ $clinic->clinic_name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <nav class="space-y-1">
                                @foreach ($leftRows as $row)
                                    <div class="{{ $row['active'] ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-slate-50' }} flex min-h-11 items-center justify-between rounded-lg px-3 py-2 transition">
                                        <div class="flex items-center gap-3">
                                            <span class="{{ $row['active'] ? 'text-emerald-700' : 'text-slate-500' }} inline-flex h-6 w-6 items-center justify-center">
                                                @switch($row['icon'])
                                                    @case('squares-2x2')
                                                        <x-heroicon-o-squares-2x2 class="h-5 w-5" />
                                                        @break
                                                    @case('building-storefront')
                                                        <x-heroicon-o-building-storefront class="h-5 w-5" />
                                                        @break
                                                    @case('users')
                                                        <x-heroicon-o-users class="h-5 w-5" />
                                                        @break
                                                    @case('cog-6-tooth')
                                                        <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
                                                        @break
                                                    @case('clock')
                                                        <x-heroicon-o-clock class="h-5 w-5" />
                                                        @break
                                                    @case('document-text')
                                                        <x-heroicon-o-document-text class="h-5 w-5" />
                                                        @break
                                                    @default
                                                        <x-heroicon-o-shield-check class="h-5 w-5" />
                                                @endswitch
                                            </span>
                                            <span class="text-sm font-semibold">{{ $row['label'] }}</span>
                                        </div>

                                        @if ($row['value'])
                                            @if ($row['value'] === 'ok')
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                                    <x-heroicon-o-check class="h-4 w-4" />
                                                </span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $row['value'] }}</span>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </nav>

                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-700">
                                        <x-heroicon-o-sparkles class="h-5 w-5" />
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-bold text-emerald-900">Future Workspace Intelligence</h3>
                                        <p class="mt-1 text-sm text-emerald-800">Coming Soon</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="min-w-0 space-y-5">
                    <section>
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-950">
                            <x-heroicon-o-chart-bar-square class="h-5 w-5 text-slate-500" />
                            Organization Overview
                        </h2>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($metricCards as $card)
                                <article class="pds-organization-card p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-700">{{ $card['label'] }}</p>
                                            <p class="mt-3 text-3xl font-bold tracking-normal text-slate-950">{{ $card['value'] }}</p>
                                            <p class="mt-1 text-sm text-slate-600">{{ $card['hint'] }}</p>
                                        </div>
                                        <span @class([
                                            'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full',
                                            'bg-blue-100 text-blue-700' => $card['tone'] === 'blue',
                                            'bg-emerald-100 text-emerald-700' => in_array($card['tone'], ['green', 'emerald'], true),
                                            'bg-violet-100 text-violet-700' => $card['tone'] === 'purple',
                                        ])>
                                            @switch($card['icon'])
                                                @case('building-office-2')
                                                    <x-heroicon-o-building-office-2 class="h-6 w-6" />
                                                    @break
                                                @case('users')
                                                    <x-heroicon-o-users class="h-6 w-6" />
                                                    @break
                                                @case('clipboard-document-check')
                                                    <x-heroicon-o-clipboard-document-check class="h-6 w-6" />
                                                    @break
                                                @default
                                                    <x-heroicon-o-check class="h-6 w-6" />
                                            @endswitch
                                        </span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="pds-organization-card">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <x-heroicon-o-shield-check class="h-7 w-7" />
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-950">Verification Readiness</h2>
                                    <p class="mt-1 text-sm text-slate-600">Your organization is {{ $readinessPercent }}% ready to perform verification</p>
                                </div>
                            </div>
                            <div class="pds-readiness-ring flex h-20 w-20 items-center justify-center rounded-full p-1.5" style="--progress: {{ $readinessPercent }}">
                                <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-white">
                                    <span class="text-sm font-bold text-slate-950">{{ $readinessPercent }}%</span>
                                    <span class="text-xs font-semibold text-emerald-700">Ready</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-x-10 gap-y-4 p-5 lg:grid-cols-2">
                            @foreach ($readiness as $item)
                                @php($isReady = ($item['status'] ?? null) === 'ready')
                                <div class="flex items-start gap-3">
                                    <span @class([
                                        'mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full',
                                        'bg-emerald-600 text-white' => $isReady,
                                        'text-amber-600' => ! $isReady,
                                    ])>
                                        @if ($isReady)
                                            <x-heroicon-o-check class="h-3.5 w-3.5" />
                                        @else
                                            <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-950">{{ $item['label'] ?? 'Readiness check' }}</p>
                                        <p class="mt-0.5 text-sm text-slate-600">{{ $item['description'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="px-5 pb-5">
                            @if (($links['settings']['visible'] ?? false) === true)
                                <a href="{{ $links['settings']['url'] }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    View Recommendations
                                    <x-heroicon-o-chevron-right class="h-4 w-4" />
                                </a>
                            @endif
                        </div>
                    </section>

                    <section class="pds-organization-card">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                <x-heroicon-o-clock class="h-5 w-5 text-slate-500" />
                                Recent Activity
                            </h2>
                            @if (($links['verifications']['visible'] ?? false) === true)
                                <a href="{{ $links['verifications']['url'] }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-700">View all</a>
                            @endif
                        </div>
                        <div class="max-h-44 overflow-hidden p-4">
                            @if ($recentVerifications->isEmpty())
                                <div class="flex min-h-36 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center">
                                    <div>
                                        <p class="text-sm font-bold text-slate-950">No recent activity</p>
                                        <p class="mt-1 text-sm text-slate-600">Recent verification movement will appear here.</p>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($recentVerifications->take(3) as $row)
                                        <article class="flex items-start justify-between gap-4 rounded-xl bg-white">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-sm font-bold text-violet-700">
                                                    {{ str($row['patient'])->substr(0, 1)->upper() }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-bold text-slate-950">{{ $row['reference'] }} - {{ $row['patient'] }}</p>
                                                    <p class="mt-1 truncate text-sm text-slate-600">{{ $row['status'] }} at {{ $row['clinic'] }}</p>
                                                </div>
                                            </div>
                                            <span class="shrink-0 text-xs text-slate-500">{{ $row['updated'] }}</span>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="pds-organization-card">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                <x-heroicon-o-cog-6-tooth class="h-5 w-5 text-slate-500" />
                                Verification Configuration Summary
                            </h2>
                            @if (($links['settings']['visible'] ?? false) === true)
                                <a href="{{ $links['settings']['url'] }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-700">Manage</a>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-3">
                            @foreach ($configurationRows as $row)
                                @php($isReady = $row['status'] === 'ready')
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-bold text-slate-950">{{ $row['label'] }}</p>
                                        <span @class([
                                            'inline-flex h-6 w-6 items-center justify-center rounded-full',
                                            'bg-emerald-100 text-emerald-700' => $isReady,
                                            'bg-amber-100 text-amber-700' => ! $isReady,
                                        ])>
                                            @if ($isReady)
                                                <x-heroicon-o-check class="h-4 w-4" />
                                            @else
                                                <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                                            @endif
                                        </span>
                                    </div>
                                    <p class="mt-3 text-2xl font-bold text-slate-950">{{ $row['value'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $row['helper'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </main>

                <aside class="min-w-0">
                    <div class="space-y-4 2xl:sticky 2xl:top-5">
                        <section class="pds-organization-card">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                    <x-heroicon-o-clock class="h-5 w-5 text-slate-500" />
                                    Activity Timeline
                                </h2>
                                <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">View all</button>
                            </div>
                            <div class="p-4">
                                @if ($activity->isEmpty())
                                    <div class="flex min-h-36 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center">
                                        <div>
                                            <p class="text-sm font-bold text-slate-950">No recent activity</p>
                                            <p class="mt-1 text-sm text-slate-600">Existing audit activity will appear here.</p>
                                        </div>
                                    </div>
                                @else
                                    <ol class="relative space-y-5 before:absolute before:left-2 before:top-2 before:h-[calc(100%-1rem)] before:w-px before:bg-slate-200">
                                        @foreach ($activity->take(5) as $index => $row)
                                            <li class="relative flex gap-4">
                                                <span @class([
                                                    'relative z-10 mt-1 h-4 w-4 shrink-0 rounded-full border-2 border-white',
                                                    'bg-emerald-600' => $index === 0,
                                                    'bg-blue-500' => $index === 1,
                                                    'bg-amber-500' => $index === 2,
                                                    'bg-slate-400' => $index > 2,
                                                ])></span>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-950">{{ $row['label'] }}</p>
                                                    <p class="mt-1 text-sm text-slate-700">{{ $row['value'] }}</p>
                                                    <p class="mt-1 text-sm text-slate-500">{{ $row['meta'] }}</p>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            </div>
                        </section>

                        <section class="pds-organization-card">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                    <x-heroicon-o-bolt class="h-5 w-5 text-slate-500" />
                                    Quick Actions
                                </h2>
                            </div>
                            <div class="space-y-2 p-4">
                                @if ($quickActions->isEmpty())
                                    <div class="flex min-h-36 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center">
                                        <div>
                                            <p class="text-sm font-bold text-slate-950">No actions available</p>
                                            <p class="mt-1 text-sm text-slate-600">Existing permissions do not expose quick actions.</p>
                                        </div>
                                    </div>
                                @else
                                    @foreach ($quickActions as $action)
                                        <a href="{{ $links[$action['key']]['url'] }}" class="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800">
                                            @switch($action['icon'])
                                                @case('user-plus')
                                                    <x-heroicon-o-user-plus class="h-4 w-4 text-slate-500" />
                                                    @break
                                                @case('document-plus')
                                                    <x-heroicon-o-document-plus class="h-4 w-4 text-slate-500" />
                                                    @break
                                                @case('link')
                                                    <x-heroicon-o-link class="h-4 w-4 text-slate-500" />
                                                    @break
                                                @default
                                                    <x-heroicon-o-cog-6-tooth class="h-4 w-4 text-slate-500" />
                                            @endswitch
                                            {{ $action['label'] }}
                                        </a>
                                    @endforeach
                                @endif
                            </div>
                        </section>

                        <section class="pds-organization-card">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                    <x-heroicon-o-bell-alert class="h-5 w-5 text-slate-500" />
                                    Notifications
                                </h2>
                            </div>
                            <div class="p-4">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-bold text-slate-950">{{ number_format($metrics['unread_notification_count']) }} unread notifications</p>
                                    <p class="mt-1 text-sm text-slate-600">Notification intelligence remains reserved for a future workspace phase.</p>
                                </div>
                            </div>
                        </section>

                        <section class="pds-organization-card">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                    <x-heroicon-o-arrows-right-left class="h-5 w-5 text-slate-500" />
                                    Recent Changes
                                </h2>
                            </div>
                            <div class="space-y-2 p-4">
                                @forelse ($activity->take(3) as $row)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <p class="text-sm font-bold text-slate-950">{{ $row['label'] }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $row['value'] }}</p>
                                    </div>
                                @empty
                                    <div class="flex min-h-36 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center">
                                        <div>
                                            <p class="text-sm font-bold text-slate-950">No recent changes</p>
                                            <p class="mt-1 text-sm text-slate-600">Changes are shown from existing records only.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </aside>
            </section>

            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white px-6 py-4 text-xs text-slate-500">
                <div class="flex flex-wrap items-center gap-6">
                    <span>&copy; 2024 ProDental EMR</span>
                    <span>Version 1.0.0</span>
                    <span>Verification Platform</span>
                    <span>Build local</span>
                </div>
                <div class="flex items-center gap-6">
                    <span>Privacy</span>
                    <span>Terms</span>
                    <span>Support</span>
                </div>
            </footer>
        @endunless
    </div>
</x-filament-panels::page>
