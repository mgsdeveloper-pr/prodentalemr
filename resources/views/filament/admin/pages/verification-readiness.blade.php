<x-filament-panels::page>
    @php
        $summary = $this->getSummary();
        $sections = $this->getSections();
        $verificationNavItems = [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => \App\Filament\Admin\Pages\VerificationSettings::getUrl(),
            ],
            [
                'key' => 'assignment',
                'label' => 'Assignment Management',
                'description' => 'Control how verification work is auto-assigned across the team.',
                'url' => \App\Filament\Admin\Pages\VerificationAssignmentManagement::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Maintain the shared insurance carrier master and clinic-specific defaults.',
                'url' => \App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource::getUrl('index'),
            ],
            [
                'key' => 'participation',
                'label' => 'Provider Participation',
                'description' => 'Manage participating and non-participating payer guidance for verifiers.',
                'url' => \App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource::getUrl('index'),
            ],
            [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'description' => 'Maintain the shared portal credential vault clinics can inherit from.',
                'url' => \App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource::getUrl('index'),
            ],
            [
                'key' => 'questions',
                'label' => 'Verification Questions',
                'description' => 'Manage prompts and section-specific question content.',
                'url' => \App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource::getUrl('index'),
            ],
            [
                'key' => 'arrangement',
                'label' => 'Question Arrangement',
                'description' => 'Reorder questions inside each verification section.',
                'url' => \App\Filament\Admin\Pages\VerificationQuestionArrangement::getUrl(),
            ],
            [
                'key' => 'notifications',
                'label' => 'Notification Control',
                'description' => 'Manage verification events, recipients, and urgent alert behavior.',
                'url' => \App\Filament\Admin\Pages\VerificationNotificationControl::getUrl(),
            ],
            [
                'key' => 'readiness',
                'label' => 'Verification Readiness',
                'description' => 'Review launch blockers, polish items, and readiness gaps.',
                'url' => \App\Filament\Admin\Pages\VerificationReadiness::getUrl(),
            ],
        ];
        $toneStyles = [
            'success' => ['border' => '#bbf7d0', 'bg' => '#f0fdf4', 'text' => '#166534'],
            'warning' => ['border' => '#fde68a', 'bg' => '#fffbeb', 'text' => '#92400e'],
            'danger' => ['border' => '#fecaca', 'bg' => '#fef2f2', 'text' => '#b91c1c'],
            'info' => ['border' => '#bfdbfe', 'bg' => '#eff6ff', 'text' => '#1d4ed8'],
        ];
    @endphp

    <x-verification-management-shell
        :items="$verificationNavItems"
        active="readiness"
        menu-title="Verification"
        menu-eyebrow="Admin Settings"
        menu-description="Configure verification output, question content, and section ordering from one workspace."
    >
    <div style="display:flex;flex-direction:column;gap:22px;">
        <section style="border:1px solid #dbe4ee;border-radius:24px;background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%);box-shadow:0 12px 28px rgba(15,23,42,0.06);overflow:hidden;">
            <div style="padding:22px 24px;border-bottom:1px solid #edf2f7;">
                <div style="display:inline-flex;align-items:center;padding:6px 11px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;">
                    Verification First Roadmap
                </div>
                <h2 style="margin:14px 0 0;font-size:30px;line-height:1.08;font-weight:800;color:#0f172a;">
                    Eligibility Verification Readiness
                </h2>
                <p style="margin:10px 0 0;max-width:980px;font-size:15px;line-height:1.7;color:#64748b;">
                    This page tracks what is complete, what is risky, and what still blocks the eligibility verification module from being truly market-ready.
                    It is intentionally focused on verification-first delivery, not the full PMS roadmap.
                </p>
            </div>

            <div style="padding:22px 24px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;">
                <div style="padding:16px 18px;border:1px solid #dbe4ee;border-radius:18px;background:#ffffff;">
                    <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Verification Questions</div>
                    <div style="margin-top:8px;font-size:28px;font-weight:800;color:#0f172a;">{{ $summary['questions'] }}</div>
                </div>
                <div style="padding:16px 18px;border:1px solid #dbe4ee;border-radius:18px;background:#ffffff;">
                    <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Verification Requests</div>
                    <div style="margin-top:8px;font-size:28px;font-weight:800;color:#0f172a;">{{ $summary['requests'] }}</div>
                </div>
                <div style="padding:16px 18px;border:1px solid #dbe4ee;border-radius:18px;background:#ffffff;">
                    <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Clinics With PDF Template</div>
                    <div style="margin-top:8px;font-size:28px;font-weight:800;color:#0f172a;">{{ $summary['clinics_with_pdf_template'] }}</div>
                </div>
                <div style="padding:16px 18px;border:1px solid #dbe4ee;border-radius:18px;background:#ffffff;">
                    <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Workspace Enabled Enrollments</div>
                    <div style="margin-top:8px;font-size:28px;font-weight:800;color:#0f172a;">{{ $summary['workspace_enabled_enrollments'] }}</div>
                </div>
            </div>
        </section>

        @foreach ($sections as $section)
            @php $tone = $toneStyles[$section['tone']] ?? $toneStyles['info']; @endphp
            <section style="border:1px solid #dbe4ee;border-radius:24px;background:#ffffff;box-shadow:0 12px 28px rgba(15,23,42,0.06);overflow:hidden;">
                <div style="padding:18px 22px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:12px;font-weight:800;letter-spacing:0.14em;text-transform:uppercase;color:{{ $tone['text'] }};">Readiness Section</div>
                        <h3 style="margin:6px 0 0;font-size:22px;font-weight:800;color:#0f172a;">{{ $section['title'] }}</h3>
                    </div>
                    <span style="display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;border:1px solid {{ $tone['border'] }};background:{{ $tone['bg'] }};color:{{ $tone['text'] }};font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">
                        {{ count($section['items']) }} items
                    </span>
                </div>
                <div style="padding:18px 22px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
                    @foreach ($section['items'] as $item)
                        @php
                            $itemTone = match($item['status']) {
                                'done' => $toneStyles['success'],
                                'risk' => $toneStyles['danger'],
                                'pending' => $toneStyles['warning'],
                                default => $toneStyles['info'],
                            };
                        @endphp
                        <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                <div style="font-size:15px;font-weight:800;color:#0f172a;">{{ $item['title'] }}</div>
                                <span style="display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;border:1px solid {{ $itemTone['border'] }};background:{{ $itemTone['bg'] }};color:{{ $itemTone['text'] }};font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">
                                    {{ $item['status'] }}
                                </span>
                            </div>
                            <div style="margin-top:10px;font-size:14px;line-height:1.7;color:#64748b;">
                                {{ $item['detail'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    </x-verification-management-shell>
</x-filament-panels::page>
