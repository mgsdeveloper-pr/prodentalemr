@php
    $statePath = $getStatePath();
    $selectedModules = collect($getState() ?? [])->filter()->values()->all();
    $groupCount = count($moduleGroups);
    $moduleCount = count($selectedModules);
@endphp

<div style="display:flex;flex-direction:column;gap:18px;">
    <section style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;border:1px solid #e6edf5;border-radius:22px;background:linear-gradient(135deg,#fffdf7 0%,#ffffff 55%,#fff8ea 100%);padding:18px 20px;box-shadow:0 12px 28px rgba(15,23,42,0.05);">
        <div style="display:flex;flex-direction:column;gap:8px;max-width:760px;">
            <span style="display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:0.14em;text-transform:uppercase;color:#b7791f;">
                <span style="width:10px;height:10px;border-radius:999px;background:#f59e0b;display:inline-block;"></span>
                Included Modules
            </span>
            <h3 style="margin:0;font-size:22px;font-weight:800;color:#0f172a;">Plan module bundle</h3>
            <p style="margin:0;font-size:14px;line-height:1.7;color:#64748b;">
                Pick the clinic-side modules included in this subscription plan. Billing can then reflect both usage limits and the actual feature bundle the customer receives.
            </p>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;min-width:260px;">
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;">
                <div style="border:1px solid #f8d79a;border-radius:18px;background:#fffaf0;padding:14px 16px;">
                    <p style="margin:0 0 4px 0;font-size:11px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#a16207;">Groups</p>
                    <p style="margin:0;font-size:24px;font-weight:800;color:#0f172a;">{{ $groupCount }}</p>
                </div>
                <div style="border:1px solid #bbf7d0;border-radius:18px;background:#f0fdf4;padding:14px 16px;">
                    <p style="margin:0 0 4px 0;font-size:11px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#15803d;">Selected</p>
                    <p style="margin:0;font-size:24px;font-weight:800;color:#0f172a;">{{ $moduleCount }}</p>
                </div>
            </div>
        </div>
    </section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;">
        @foreach ($moduleGroups as $groupLabel => $group)
            @php
                $modules = $group['modules'];
                $selectedInGroup = collect($modules)->filter(fn (string $module): bool => in_array($module, $selectedModules, true))->count();
            @endphp

            <section style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;padding:18px;box-shadow:0 10px 24px rgba(15,23,42,0.04);display:flex;flex-direction:column;gap:16px;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <h4 style="margin:0;font-size:16px;font-weight:800;color:#0f172a;">{{ $groupLabel }}</h4>
                        <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">{{ $group['description'] }}</p>
                    </div>

                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:44px;padding:7px 10px;border-radius:999px;background:#f8fafc;color:#475569;font-size:12px;font-weight:800;">{{ $selectedInGroup }}/{{ count($modules) }}</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach ($modules as $moduleKey)
                        @php
                            $selected = in_array($moduleKey, $selectedModules, true);
                        @endphp

                        <label style="display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:14px 14px;border-radius:18px;border:1px solid {{ $selected ? '#f8d79a' : '#e5e7eb' }};background:{{ $selected ? 'linear-gradient(135deg,#fffaf0 0%,#ffffff 100%)' : '#ffffff' }};box-shadow:{{ $selected ? '0 10px 18px rgba(245,158,11,0.10)' : 'none' }};cursor:pointer;text-align:left;">
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;">
                                <span style="font-size:14px;font-weight:800;color:#0f172a;">{{ $moduleLabels[$moduleKey] ?? str($moduleKey)->replace('_', ' ')->headline() }}</span>
                                <span style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">{{ str($moduleKey)->replace('_', ' ')->headline() }}</span>
                            </div>

                            <span style="position:relative;display:inline-flex;align-items:center;width:54px;height:30px;border-radius:999px;border:1px solid {{ $selected ? '#f59e0b' : '#d1d5db' }};background:{{ $selected ? 'linear-gradient(135deg,#f59e0b 0%,#fbbf24 100%)' : '#e5e7eb' }};flex-shrink:0;">
                                <span style="position:absolute;left:{{ $selected ? '28px' : '4px' }};width:22px;height:22px;border-radius:999px;background:#ffffff;box-shadow:0 4px 10px rgba(15,23,42,0.16);transition:left .2s ease;"></span>
                            </span>

                            <input
                                type="checkbox"
                                value="{{ $moduleKey }}"
                                wire:model.live="{{ $statePath }}"
                                style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;"
                            />
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
