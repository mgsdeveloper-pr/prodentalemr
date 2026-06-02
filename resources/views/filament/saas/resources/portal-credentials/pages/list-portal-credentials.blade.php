<x-filament-panels::page>
    @php($credentials = $this->getPortalCredentials())

    <div style="display: flex; flex-direction: column; gap: 22px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7;">
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #ecfeff; border: 1px solid #99f6e4; color: #0f766e; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; width: fit-content;">
                        Verification Workspace
                    </div>
                    <h2 style="margin: 0; font-size: 30px; line-height: 1.08; font-weight: 800; color: #0f172a;">
                        Portal Credentials
                    </h2>
                    <p style="margin: 0; max-width: 920px; font-size: 15px; line-height: 1.7; color: #64748b;">
                        Review the clinic-specific portal link, username, and password in one place. Credential additions, removals, and maintenance are handled from the Verification Settings section only.
                    </p>
                    <div class="portal-credential-header-grid" style="display: grid; grid-template-columns: minmax(260px, 340px) minmax(320px, 1fr); gap: 14px; align-items: stretch;">
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); display: flex; flex-direction: column; justify-content: center; min-height: 78px;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Clinic</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $this->getSelectedClinicName() ?: 'Select clinic scope' }}</div>
                        </div>
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); display: flex; flex-direction: column; justify-content: center; min-height: 78px;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Search</div>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; pointer-events: none;">
                                    <x-heroicon-o-magnifying-glass style="width: 18px; height: 18px;" />
                                </span>
                                <input
                                    type="search"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Search portals"
                                    style="width: 100%; padding: 12px 14px 12px 42px; border-radius: 16px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04); min-height: 48px;"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="padding: 20px 24px 24px;">
                @if ($credentials->isEmpty())
                    <div style="border: 1px dashed #cbd5e1; border-radius: 20px; padding: 24px; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                        No portal credentials are available yet.
                    </div>
                @else
                    <div class="portal-credential-card-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px;">
                        @foreach ($credentials as $credential)
                            <article style="border: 1px solid #dbe4ee; border-radius: 22px; background: #ffffff; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05); overflow: hidden; height: 100%;">
                                <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                                    <div>
                                        <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;">{{ $credential->portal_name }}</h3>
                                        <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                            <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; font-size: 11px; font-weight: 700;">
                                                {{ \App\Models\PortalCredential::CATEGORY_OPTIONS[$credential->portal_category ?: 'other'] ?? 'Other' }}
                                            </span>
                                            <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid {{ $credential->is_active ? '#86efac' : '#d1d5db' }}; background: {{ $credential->is_active ? '#f0fdf4' : '#f8fafc' }}; color: {{ $credential->is_active ? '#15803d' : '#64748b' }}; font-size: 11px; font-weight: 700;">
                                                {{ $credential->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    @if ($this->canUpdatePasswords())
                                        <button
                                            type="button"
                                            wire:click="openPasswordEditor({{ $credential->getKey() }})"
                                            style="display: inline-flex; align-items: center; justify-content: center; padding: 8px 14px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 12px; font-weight: 700; white-space: nowrap;"
                                        >
                                            Edit
                                        </button>
                                    @endif
                                </div>

                                <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 16px;">
                                    <div>
                                        <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Portal Link</div>
                                        @if (filled($credential->login_url))
                                            <a href="{{ $credential->login_url }}" target="_blank" rel="noopener noreferrer" style="font-size: 14px; font-weight: 700; color: #2563eb; word-break: break-all; text-decoration: none;">
                                                {{ $credential->login_url }}
                                            </a>
                                        @else
                                            <div style="font-size: 14px; color: #94a3b8;">No portal link available</div>
                                        @endif
                                    </div>

                                    <div>
                                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Username</div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 16px; border: 1px solid #e2e8f0; background: #f8fafc; min-height: 60px;">
                                            <span id="portal-username-admin-card-{{ $credential->getKey() }}" data-masked="{{ \App\Models\PortalCredential::maskSecret($credential->username) }}" data-visible="0" style="font-size: 14px; font-weight: 700; color: #334155;">
                                                {{ \App\Models\PortalCredential::maskSecret($credential->username) }}
                                            </span>
                                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                <button type="button" onclick="togglePortalSecret('portal-username-admin-card-{{ $credential->getKey() }}', @js($credential->username), this)" title="View username" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;">
                                                    <x-heroicon-o-eye style="width: 18px; height: 18px;" />
                                                </button>
                                                <button type="button" onclick="copyPortalSecret(@js($credential->username), this)" title="Copy username" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;">
                                                    <x-heroicon-o-clipboard style="width: 18px; height: 18px;" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Password</div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 16px; border: 1px solid #e2e8f0; background: #f8fafc; min-height: 60px;">
                                            <span id="portal-password-admin-card-{{ $credential->getKey() }}" data-masked="{{ \App\Models\PortalCredential::maskSecret($credential->password) }}" data-visible="0" style="font-size: 14px; font-weight: 700; color: #334155;">
                                                {{ \App\Models\PortalCredential::maskSecret($credential->password) }}
                                            </span>
                                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                <button type="button" onclick="togglePortalSecret('portal-password-admin-card-{{ $credential->getKey() }}', @js($credential->password), this)" title="View password" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;">
                                                    <x-heroicon-o-eye style="width: 18px; height: 18px;" />
                                                </button>
                                                <button type="button" onclick="copyPortalSecret(@js($credential->password), this)" title="Copy password" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;">
                                                    <x-heroicon-o-clipboard style="width: 18px; height: 18px;" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    @if ($this->passwordModalOpen)
        <div wire:keydown.escape.window="closePasswordEditor" style="position: fixed; inset: 0; z-index: 50; background: rgba(15, 23, 42, 0.48); display: flex; align-items: center; justify-content: center; padding: 24px;">
            <div style="width: min(100%, 520px); border-radius: 24px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 30px 60px rgba(15, 23, 42, 0.24); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;">Password Update</div>
                    <h3 style="margin: 14px 0 0; font-size: 24px; font-weight: 800; color: #0f172a;">Update Portal Password</h3>
                    <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                        Change the password for <strong style="color: #0f172a;">{{ $this->editingCredentialName }}</strong>. The previous password will be saved in audit history, with only the last 5 passwords retained.
                    </p>
                </div>

                <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 16px;">
                    <div class="portal-password-modal-grid" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;">
                        <div style="padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Portal Link</div>
                            @if (filled($this->editingCredentialLink))
                                <a href="{{ $this->editingCredentialLink }}" target="_blank" rel="noopener noreferrer" style="font-size: 13px; font-weight: 700; color: #2563eb; word-break: break-all; text-decoration: none;">
                                    {{ $this->editingCredentialLink }}
                                </a>
                            @else
                                <div style="font-size: 13px; color: #94a3b8;">No portal link available</div>
                            @endif
                        </div>
                        <div style="padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Username</div>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <span id="portal-username-admin-modal" data-masked="{{ filled($this->editingCredentialUsername) ? \App\Models\PortalCredential::maskSecret($this->editingCredentialUsername) : '-' }}" data-visible="0" style="font-size: 13px; font-weight: 700; color: #334155; word-break: break-all;">
                                    {{ filled($this->editingCredentialUsername) ? \App\Models\PortalCredential::maskSecret($this->editingCredentialUsername) : '-' }}
                                </span>
                                @if (filled($this->editingCredentialUsername))
                                    <div style="display: inline-flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                        <button type="button" onclick="togglePortalSecret('portal-username-admin-modal', @js($this->editingCredentialUsername), this)" title="View username" style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;">
                                            <x-heroicon-o-eye style="width: 18px; height: 18px;" />
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 700; color: #334155;">New Password</label>
                        <input type="password" wire:model.defer="newPassword" autocomplete="new-password" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;">
                        @error('newPassword')
                            <div style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 700; color: #334155;">Confirm Password</label>
                        <input type="password" wire:model.defer="newPasswordConfirmation" autocomplete="new-password" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;">
                        @error('newPasswordConfirmation')
                            <div style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="padding: 18px 22px; border-top: 1px solid #edf2f7; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                    <button type="button" wire:click="closePasswordEditor" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700;">
                        Cancel
                    </button>
                    <button type="button" wire:click="updateCredentialPassword" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 14px; border: 1px solid #f59e0b; background: #f59e0b; color: #ffffff; font-size: 13px; font-weight: 700;">
                        Save Password
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
        .portal-credential-card-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        @media (max-width: 900px) {
            .portal-credential-card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 1200px) {
            .portal-credential-card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 800px) {
            .portal-credential-card-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 820px) {
            .portal-credential-header-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 700px) {
            .portal-password-modal-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
    </style>

    <script>
        function togglePortalSecret(targetId, rawValue, button) {
            const target = document.getElementById(targetId);
            if (! target) return;

            const visible = target.getAttribute('data-visible') === '1';
            if (visible) {
                target.textContent = target.getAttribute('data-masked') || '******';
                target.setAttribute('data-visible', '0');
                button.title = button.title.replace('Hide', 'View');
                return;
            }

            target.textContent = rawValue || '-';
            target.setAttribute('data-visible', '1');
            button.title = button.title.replace('View', 'Hide');
        }

        async function copyPortalSecret(rawValue, button) {
            if (! rawValue) return;

            await navigator.clipboard.writeText(rawValue);
            const original = button.title;
            button.title = original.replace('Copy', 'Copied');
            setTimeout(() => {
                button.title = original;
            }, 1200);
        }
    </script>
</x-filament-panels::page>
