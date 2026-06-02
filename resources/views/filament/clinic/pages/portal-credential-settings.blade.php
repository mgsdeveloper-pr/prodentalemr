<x-filament-panels::page>
    @php
        $clinic = $this->getSelectedClinic();
        $showPortalCredentials = \App\Support\VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService();
        $verificationNavItems = [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => \App\Filament\Clinic\Pages\VerificationSettings::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Browse the shared carrier master and manage clinic-specific changes.',
                'url' => \App\Filament\Clinic\Resources\InsuranceCarriers\InsuranceCarrierResource::getUrl('index'),
            ],
        ];
        if ($showPortalCredentials) {
            $verificationNavItems[] = [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'description' => 'Keep clinic-specific website and payer portal credentials without using spreadsheets.',
                'url' => \App\Filament\Clinic\Pages\PortalCredentialSettings::getUrl(),
            ];
        }
        $verificationNavItems[] = [
            'key' => 'questions',
            'label' => 'Verification Questions',
            'description' => 'Manage prompts and section-specific question content.',
            'url' => \App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource::getUrl('index'),
        ];
        $verificationNavItems[] = [
            'key' => 'arrangement',
            'label' => 'Question Arrangement',
            'description' => 'Reorder questions inside each verification section.',
            'url' => \App\Filament\Clinic\Pages\VerificationQuestionArrangement::getUrl(),
        ];
    @endphp

    <x-verification-management-shell
        :items="$verificationNavItems"
        active="credentials"
        menu-title="Verification"
        menu-eyebrow="Clinic Settings"
        menu-description="Configure verification output, insurance master data, portal credentials, question content, and section ordering from one workspace."
    >
        <div style="display: flex; flex-direction: column; gap: 22px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Portal Credential Management</div>
                    <h3 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">Clinic Portal Overrides</h3>
                    <p style="margin: 10px 0 0; max-width: 820px; font-size: 14px; line-height: 1.7; color: #64748b;">
                        Review and maintain the portal credentials assigned to this clinic. Other clinics will not see these records.
                    </p>
                </div>

                <div style="padding: 18px 22px;">
                    @if (! $this->canManagePortalCredentials())
                        <div style="border: 1px dashed #cbd5e1; border-radius: 18px; padding: 22px; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Your account does not currently have permission to manage clinic portal credential overrides.
                        </div>
                    @else
                        @php($credentials = $this->getPortalCredentials())
                        @if ($credentials->isEmpty())
                            <div style="border: 1px dashed #cbd5e1; border-radius: 18px; padding: 22px; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                                No portal credentials are available yet. The verification team can add shared credentials from the Verification panel.
                            </div>
                        @else
                            <div style="display: flex; justify-content: flex-end; margin-bottom: 14px;">
                                <div style="min-width: 240px;">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.300ms="search"
                                        placeholder="Search credentials"
                                        style="width: 100%; padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;"
                                    >
                                </div>
                            </div>
                            <div style="border: 1px solid #dbe4ee; border-radius: 20px; overflow: hidden;">
                                <div style="overflow-x: auto;">
                                    <table style="width: 100%; min-width: 1160px; border-collapse: collapse;">
                                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                            <tr>
                                                <th style="padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 800; color: #0f172a;">Portal</th>
                                                <th style="padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 800; color: #0f172a;">Category</th>
                                                <th style="padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 800; color: #0f172a;">Portal Link</th>
                                                <th style="padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 800; color: #0f172a;">Username</th>
                                                <th style="padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 800; color: #0f172a;">Password</th>
                                                <th style="padding: 14px 16px; text-align: center; font-size: 13px; font-weight: 800; color: #0f172a;">Active</th>
                                                <th style="padding: 14px 16px; text-align: center; font-size: 13px; font-weight: 800; color: #0f172a;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($credentials as $credential)
                                                <tr style="border-bottom: 1px solid #edf2f7;">
                                                    <td style="padding: 16px; font-size: 14px; font-weight: 800; color: #0f172a; vertical-align: middle;">{{ $credential->portal_name }}</td>
                                                    <td style="padding: 16px; vertical-align: middle;">
                                                        <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                                            {{ \App\Models\PortalCredential::CATEGORY_OPTIONS[$credential->portal_category ?: 'other'] ?? 'Other' }}
                                                        </span>
                                                    </td>
                                                    <td style="padding: 16px; vertical-align: middle;">
                                                        @if (filled($credential->login_url))
                                                            <a href="{{ $credential->login_url }}" target="_blank" rel="noopener noreferrer" style="font-size: 13px; font-weight: 700; color: #2563eb; text-decoration: none; word-break: break-all;">
                                                                {{ $credential->login_url }}
                                                            </a>
                                                        @else
                                                            <span style="font-size: 13px; color: #94a3b8;">-</span>
                                                        @endif
                                                    </td>
                                                    <td style="padding: 16px; vertical-align: middle;">
                                                        <div style="display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;">
                                                            <span id="portal-username-clinic-{{ $credential->getKey() }}" data-masked="{{ \App\Models\PortalCredential::maskSecret($credential->username) }}" data-visible="0" style="font-size: 13px; color: #64748b; font-weight: 700;">
                                                                {{ \App\Models\PortalCredential::maskSecret($credential->username) }}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                onclick="togglePortalSecret('portal-username-clinic-{{ $credential->getKey() }}', @js($credential->username), this)"
                                                                title="View username"
                                                                style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;"
                                                            >
                                                                <x-heroicon-o-eye style="width: 16px; height: 16px;" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onclick="copyPortalSecret(@js($credential->username), this)"
                                                                title="Copy username"
                                                                style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;"
                                                            >
                                                                <x-heroicon-o-clipboard style="width: 16px; height: 16px;" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 16px; vertical-align: middle;">
                                                        <div style="display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;">
                                                            <span id="portal-password-clinic-{{ $credential->getKey() }}" data-masked="{{ \App\Models\PortalCredential::maskSecret($credential->password) }}" data-visible="0" style="font-size: 13px; color: #64748b; font-weight: 700;">
                                                                {{ \App\Models\PortalCredential::maskSecret($credential->password) }}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                onclick="togglePortalSecret('portal-password-clinic-{{ $credential->getKey() }}', @js($credential->password), this)"
                                                                title="View password"
                                                                style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;"
                                                            >
                                                                <x-heroicon-o-eye style="width: 16px; height: 16px;" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onclick="copyPortalSecret(@js($credential->password), this)"
                                                                title="Copy password"
                                                                style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a;"
                                                            >
                                                                <x-heroicon-o-clipboard style="width: 16px; height: 16px;" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 16px; text-align: center; vertical-align: middle;">
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; border-radius: 999px; border: 1px solid {{ $credential->is_active ? '#86efac' : '#d1d5db' }}; background: {{ $credential->is_active ? '#f0fdf4' : '#f8fafc' }}; color: {{ $credential->is_active ? '#15803d' : '#64748b' }}; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                                            {{ $credential->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td style="padding: 16px; text-align: center; vertical-align: middle;">
                                                        <div style="display: inline-flex; align-items: center; gap: 10px; white-space: nowrap;">
                                                            @if ($this->canEditPortalCredentials())
                                                                <button
                                                                    type="button"
                                                                    wire:click="editPortalCredential({{ $credential->getKey() }})"
                                                                    style="display: inline-flex; align-items: center; justify-content: center; padding: 8px 12px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 12px; font-weight: 800;"
                                                                >
                                                                    Edit
                                                                </button>
                                                                @if ($this->canDeletePortalCredentials())
                                                                    <button
                                                                        type="button"
                                                                        wire:click="deletePortalCredential({{ $credential->getKey() }})"
                                                                        wire:confirm="Remove this clinic portal credential?"
                                                                        style="display: inline-flex; align-items: center; justify-content: center; padding: 8px 12px; border-radius: 12px; border: 1px solid #fecaca; background: #fff1f2; color: #dc2626; font-size: 12px; font-weight: 800;"
                                                                    >
                                                                        Delete
                                                                    </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </section>
        </div>
    </x-verification-management-shell>
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
