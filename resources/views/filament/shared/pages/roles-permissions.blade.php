<x-filament-panels::page>
    @php
        $roleOptions = $this->getRoleOptions();
        $modules = $this->visibleModules;
        $actions = $this->actionLabels;
        $canEditSelectedRole = $this->canEditSelectedRole();
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px;">
        <section style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;">
            <div>
                <p style="margin:0;max-width:760px;font-size:15px;line-height:1.7;color:#64748b;">
                    Control {{ str($this->getPanelLabel())->lower() }} access by role. Each row maps one module to basic add, view, update, and delete rights.
                </p>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button
                    type="button"
                    wire:click="openCreateRoleModal"
                    style="display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:14px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:13px;font-weight:700;cursor:pointer;"
                >
                    Create role
                </button>
                <button
                    type="button"
                    wire:click="resetRolePermissions"
                    style="display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:14px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:13px;font-weight:700;cursor:pointer;"
                >
                    Reset
                </button>
                <button
                    type="button"
                    wire:click="savePermissions"
                    @disabled(! $canEditSelectedRole)
                    style="display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border:0;border-radius:14px;background:{{ $canEditSelectedRole ? 'linear-gradient(135deg,#0f766e 0%,#0ea5a4 100%)' : '#cbd5e1' }};color:#ffffff;font-size:13px;font-weight:800;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};box-shadow:{{ $canEditSelectedRole ? '0 10px 22px rgba(15,118,110,0.22)' : 'none' }};"
                >
                    Save permissions
                </button>
            </div>
        </section>

        @unless ($canEditSelectedRole)
            <section style="border:1px solid #fde68a;border-radius:18px;background:#fffbeb;padding:14px 16px;color:#92400e;font-size:13px;font-weight:600;">
                The selected role is protected. Its permissions are shown for reference only and cannot be changed here.
            </section>
        @endunless

        @if ($this->showCreateRoleModal)
            <div style="position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:24px;background:rgba(15,23,42,0.36);">
                <div style="width:min(100%,560px);border:1px solid #dbe4ee;border-radius:26px;background:#ffffff;box-shadow:0 28px 70px rgba(15,23,42,0.18);overflow:hidden;">
                    <div style="padding:22px 24px;border-bottom:1px solid #edf2f7;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <span style="display:inline-flex;align-items:center;padding:6px 11px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;width:fit-content;">
                                New Role
                            </span>
                            <div>
                                <h3 style="margin:0;font-size:30px;font-weight:800;color:#0f172a;">Create Role</h3>
                                <p style="margin:8px 0 0;font-size:14px;line-height:1.7;color:#64748b;">
                                    Add a new {{ str($this->getPanelLabel())->lower() }} role, then assign its module permissions from the matrix.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="closeCreateRoleModal"
                            style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:999px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:20px;cursor:pointer;"
                        >
                            &times;
                        </button>
                    </div>

                    <div style="padding:22px 24px;display:flex;flex-direction:column;gap:18px;">
                        <label style="display:flex;flex-direction:column;gap:8px;">
                            <span style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#475569;">Role name</span>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="newRoleName"
                                placeholder="Example: Senior Verifier"
                                style="width:100%;min-height:46px;padding:11px 12px;border:1px solid #d6dde8;border-radius:12px;background:#ffffff;color:#0f172a;font-size:14px;"
                            />
                            @error('newRoleName')
                                <span style="font-size:12px;color:#dc2626;font-weight:600;">{{ $message }}</span>
                            @enderror
                        </label>

                        <div style="padding:14px 16px;border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">System role key</div>
                            <div style="margin-top:6px;font-size:14px;font-weight:700;color:#0f172a;">{{ $this->newRoleKeyPreview }}</div>
                        </div>
                    </div>

                    <div style="padding:18px 24px;border-top:1px solid #edf2f7;display:flex;align-items:center;justify-content:flex-end;gap:10px;">
                        <button
                            type="button"
                            wire:click="closeCreateRoleModal"
                            style="display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:14px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:13px;font-weight:700;cursor:pointer;"
                        >
                            Close
                        </button>
                        <button
                            type="button"
                            wire:click="createRole"
                            style="display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border:0;border-radius:14px;background:linear-gradient(135deg,#0f766e 0%,#0ea5a4 100%);color:#ffffff;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 10px 22px rgba(15,118,110,0.22);"
                        >
                            Create role
                        </button>
                    </div>
                </div>
            </div>
        @endif

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

            <div style="overflow:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0;">
                    <thead>
                        <tr style="background:linear-gradient(90deg,#eff6ff 0%,#f8fafc 100%);">
                            <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#475569;border-bottom:1px solid #dbeafe;">Module</th>
                            @foreach ($actions as $actionKey => $actionLabel)
                                <th style="padding:12px 16px;text-align:center;border-bottom:1px solid #dbeafe;">
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                                        <span style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#475569;">{{ $actionLabel }}</span>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;">
                                            <button
                                                type="button"
                                                wire:click="setAllForAction('{{ $actionKey }}', true)"
                                                @disabled(! $canEditSelectedRole)
                                                style="padding:5px 8px;border-radius:999px;border:1px solid {{ $canEditSelectedRole ? '#bfdbfe' : '#e2e8f0' }};background:{{ $canEditSelectedRole ? '#eff6ff' : '#f8fafc' }};color:{{ $canEditSelectedRole ? '#1d4ed8' : '#94a3b8' }};font-size:11px;font-weight:700;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};"
                                            >
                                                All
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="setAllForAction('{{ $actionKey }}', false)"
                                                @disabled(! $canEditSelectedRole)
                                                style="padding:5px 8px;border-radius:999px;border:1px solid #e2e8f0;background:{{ $canEditSelectedRole ? '#ffffff' : '#f8fafc' }};color:{{ $canEditSelectedRole ? '#64748b' : '#94a3b8' }};font-size:11px;font-weight:700;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};"
                                            >
                                                None
                                            </button>
                                        </div>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($modules as $moduleKey => $moduleLabel)
                            <tr>
                                <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;">
                                    <div style="display:flex;flex-direction:column;gap:4px;">
                                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                            <span style="font-size:14px;font-weight:700;color:#0f172a;">{{ $moduleLabel }}</span>
                                            @if ($moduleKey === 'verification')
                                                <span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">
                                                    Verification Panel
                                                </span>
                                            @endif
                                        </div>
                                        <span style="font-size:12px;color:#64748b;">
                                            {{ $moduleKey === 'verification'
                                                ? 'Controls access to the /verification workspace and its managed-service queue.'
                                                : str($moduleKey)->replace('_', ' ')->headline() }}
                                        </span>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">
                                            <button
                                                type="button"
                                                wire:click="setAllForModule('{{ $moduleKey }}', true)"
                                                @disabled(! $canEditSelectedRole)
                                                style="padding:5px 8px;border-radius:999px;border:1px solid {{ $canEditSelectedRole ? '#bbf7d0' : '#e2e8f0' }};background:{{ $canEditSelectedRole ? '#ecfdf5' : '#f8fafc' }};color:{{ $canEditSelectedRole ? '#15803d' : '#94a3b8' }};font-size:11px;font-weight:700;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};"
                                            >
                                                Select all
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="setAllForModule('{{ $moduleKey }}', false)"
                                                @disabled(! $canEditSelectedRole)
                                                style="padding:5px 8px;border-radius:999px;border:1px solid #e2e8f0;background:{{ $canEditSelectedRole ? '#ffffff' : '#f8fafc' }};color:{{ $canEditSelectedRole ? '#64748b' : '#94a3b8' }};font-size:11px;font-weight:700;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};"
                                            >
                                                Clear
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                @foreach ($actions as $actionKey => $actionLabel)
                                    <td style="padding:16px;border-bottom:1px solid #edf2f7;text-align:center;">
                                        <label style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer;">
                                            <input
                                                type="checkbox"
                                                wire:model.live="matrix.{{ $moduleKey }}.{{ $actionKey }}"
                                                @disabled(! $canEditSelectedRole)
                                                style="width:18px;height:18px;border-radius:6px;border:1px solid #cbd5e1;accent-color:#0ea5a4;cursor:{{ $canEditSelectedRole ? 'pointer' : 'not-allowed' }};"
                                            />
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($actions) + 1 }}" style="padding:22px 18px;text-align:center;color:#64748b;">
                                    No modules match your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
