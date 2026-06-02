<x-filament-panels::page>
    @php
        $roleOptions = $this->getRoleOptions();
        $modules = $this->visibleModules;
        $canEditSelectedRole = $this->canEditSelectedRole();
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px;">
        <section style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;">
            <div>
                <p style="margin:0;max-width:760px;font-size:15px;line-height:1.7;color:#64748b;">
                    Choose which modules each role is allowed to see inside this panel. Users will only see enabled modules in navigation and direct page access.
                </p>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button
                    type="button"
                    wire:click="resetModuleSettings"
                    style="display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:14px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:13px;font-weight:700;cursor:pointer;"
                >
                    Reset
                </button>
                <button
                    type="button"
                    wire:click="saveModuleSettings"
                    @disabled(! $canEditSelectedRole)
                    style="display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border:0;border-radius:14px;background:{{ $canEditSelectedRole ? 'linear-gradient(135deg,#0f766e 0%,#0ea5a4 100%)' : '#cbd5e1' }};color:#ffffff;font-size:13px;font-weight:800;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};box-shadow:{{ $canEditSelectedRole ? '0 10px 22px rgba(15,118,110,0.22)' : 'none' }};"
                >
                    Save Modules
                </button>
            </div>
        </section>

        @unless ($canEditSelectedRole)
            <section style="border:1px solid #fde68a;border-radius:18px;background:#fffbeb;padding:14px 16px;color:#92400e;font-size:13px;font-weight:600;">
                This role is protected. Its module visibility is shown for reference only and cannot be changed here.
            </section>
        @endunless

        <section style="border:1px solid #e5e7eb;border-radius:24px;background:#ffffff;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,0.06);">
            <div style="padding:18px 20px;border-bottom:1px solid #edf2f7;display:grid;grid-template-columns:minmax(220px,320px) minmax(220px,1fr);gap:14px;">
                <label style="display:flex;flex-direction:column;gap:8px;">
                    <span style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#475569;">Role</span>
                    <select wire:model.live="selectedRole" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid #d6dde8;border-radius:12px;background:#ffffff;color:#0f172a;font-size:14px;">
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label style="display:flex;flex-direction:column;gap:8px;">
                    <span style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#475569;">Search module</span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search modules..."
                        style="width:100%;min-height:44px;padding:10px 12px;border:1px solid #d6dde8;border-radius:12px;background:#ffffff;color:#0f172a;font-size:14px;"
                    />
                </label>
            </div>

            <div style="padding:18px 20px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;background:linear-gradient(90deg,#fffaf0 0%,#ffffff 65%);">
                <div>
                    <h3 style="margin:0 0 6px 0;font-size:18px;font-weight:800;color:#0f172a;">Available Modules</h3>
                    <p style="margin:0;font-size:14px;color:#64748b;">Admins can quickly enable or disable visibility for all modules in one place.</p>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button
                        type="button"
                        wire:click="setAllModules(true)"
                        @disabled(! $canEditSelectedRole)
                        style="padding:9px 14px;border-radius:999px;border:1px solid {{ $canEditSelectedRole ? '#bbf7d0' : '#e2e8f0' }};background:{{ $canEditSelectedRole ? '#ecfdf5' : '#f8fafc' }};color:{{ $canEditSelectedRole ? '#15803d' : '#94a3b8' }};font-size:12px;font-weight:800;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};"
                    >
                        Enable all
                    </button>
                    <button
                        type="button"
                        wire:click="setAllModules(false)"
                        @disabled(! $canEditSelectedRole)
                        style="padding:9px 14px;border-radius:999px;border:1px solid #e2e8f0;background:{{ $canEditSelectedRole ? '#ffffff' : '#f8fafc' }};color:{{ $canEditSelectedRole ? '#64748b' : '#94a3b8' }};font-size:12px;font-weight:800;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};"
                    >
                        Disable all
                    </button>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;padding:20px;">
                @forelse ($modules as $moduleKey => $moduleLabel)
                    <div style="border:1px solid #e5e7eb;border-radius:20px;background:#ffffff;padding:18px 18px 16px;box-shadow:0 8px 20px rgba(15,23,42,0.05);display:flex;align-items:center;justify-content:space-between;gap:14px;">
                        <div style="min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 4px 0;">
                                <p style="margin:0;font-size:15px;font-weight:800;color:#0f172a;">{{ $moduleLabel }}</p>
                                @if ($moduleKey === 'verification')
                                    <span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">
                                        Verification Panel
                                    </span>
                                @endif
                            </div>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">
                                {{ $moduleKey === 'verification'
                                    ? 'Show or hide the /verification workspace for this role.'
                                    : str($moduleKey)->replace('_', ' ')->headline() }}
                            </p>
                        </div>

                        <button
                            type="button"
                            role="switch"
                            aria-checked="{{ ($this->modules[$moduleKey] ?? false) ? 'true' : 'false' }}"
                            wire:click="$toggle('modules.{{ $moduleKey }}')"
                            @disabled(! $canEditSelectedRole)
                            style="position:relative;display:inline-flex;align-items:center;width:58px;height:32px;border-radius:999px;border:1px solid {{ ($this->modules[$moduleKey] ?? false) ? '#f59e0b' : '#d1d5db' }};background:{{ ($this->modules[$moduleKey] ?? false) ? 'linear-gradient(135deg,#f59e0b 0%,#fbbf24 100%)' : '#e5e7eb' }};cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};opacity:{{ $canEditSelectedRole ? '1' : '0.65' }};"
                        >
                            <span style="position:absolute;left:{{ ($this->modules[$moduleKey] ?? false) ? '28px' : '4px' }};width:24px;height:24px;border-radius:999px;background:#ffffff;box-shadow:0 4px 10px rgba(15,23,42,0.16);transition:left .2s ease;"></span>
                        </button>
                    </div>
                @empty
                    <div style="grid-column:1 / -1;border:1px dashed #cbd5e1;border-radius:20px;background:#f8fafc;padding:24px;text-align:center;color:#64748b;">
                        No modules matched your search.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
