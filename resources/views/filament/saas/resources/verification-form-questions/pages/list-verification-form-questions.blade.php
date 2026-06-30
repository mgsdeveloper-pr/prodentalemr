<x-filament-panels::page>
    @php
        $builtInSections = $this->getBuiltInSections();
        $selectedClinicName = $this->getSelectedClinicName();
        $verificationNavItems = \App\Support\VerificationSettingsNavigation::items();
    @endphp

    <x-verification-management-shell
        :items="$verificationNavItems"
        active="questions"
        menu-title="Verification"
        menu-eyebrow="Admin Settings"
        menu-description="Configure verification output, question content, and section ordering from one workspace."
    >
    <div style="display: flex; flex-direction: column; gap: 22px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7;">
                <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase;">
                    Verification Workspace
                </div>
                <h2 style="margin: 14px 0 0; font-size: 30px; line-height: 1.08; font-weight: 800; color: #0f172a;">
                    Template Builder
                </h2>
                <p style="margin: 10px 0 0; max-width: 980px; font-size: 15px; line-height: 1.7; color: #64748b;">
                    Manage Template 2 section by section, including response datatypes, dropdown options, placeholders, and optional note areas.
                </p>
            </div>

            @if ($selectedClinicName)
                <div style="padding: 18px 24px 0;">
                    <div style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid #dbeafe; border-radius: 14px; background: #f8fbff; color: #1e3a8a; font-size: 13px; font-weight: 700;">
                        Viewing current question set for clinic:
                        <span style="color: #0f172a;">{{ $selectedClinicName }}</span>
                    </div>
                </div>
                <div style="padding: 22px 24px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px;">
                    @foreach ($builtInSections as $section)
                        <section style="border: 1px solid #dbe4ee; border-radius: 20px; background: #ffffff; overflow: hidden;">
                            <div style="padding: 14px 16px; border-bottom: 1px solid #edf2f7; background: linear-gradient(90deg, #eff6ff 0%, #f8fafc 100%);">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                        {{ $section['title'] }}
                                    </h3>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <span style="display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;border:1px solid #dbe4ee;background:#f8fafc;color:#475569;font-size:12px;font-weight:700;">{{ $section['count'] }} total</span>
                                        <span style="display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;border:1px solid #bbf7d0;background:#f0fdf4;color:#166534;font-size:12px;font-weight:700;">{{ $section['active_count'] }} active</span>
                                        <span style="display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700;">{{ $section['system_count'] }} system</span>
                                    </div>
                                </div>
                            </div>
                            <div style="padding: 14px 16px; display:flex; flex-direction:column; gap:10px;">
                                @foreach ($section['questions'] as $question)
                                    <div style="padding:12px 14px;border:1px solid #e5e7eb;border-radius:16px;background:#ffffff;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                            <div style="font-size:14px;font-weight:700;color:#0f172a;">{{ $question['prompt'] }}</div>
                                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                                <span style="display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;border:1px solid #dbe4ee;background:#f8fafc;color:#475569;font-size:11px;font-weight:700;">{{ $question['form_type'] }}</span>
                                                @if ($question['is_builtin'])
                                                    <span style="display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:700;">System</span>
                                                @endif
                                                @if ($question['is_active'])
                                                    <span style="display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;border:1px solid #bbf7d0;background:#f0fdf4;color:#166534;font-size:11px;font-weight:700;">Active</span>
                                                @else
                                                    <span style="display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;font-size:11px;font-weight:700;">Inactive</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @if ($section['count'] > 5)
                                    <div style="font-size:12px;font-weight:700;color:#94a3b8;">+ {{ $section['count'] - 5 }} more question(s) in this section</div>
                                @endif
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                <div style="padding: 22px 24px;">
                    <div style="border: 1px dashed #cbd5e1; border-radius: 20px; background: #f8fafc; padding: 26px; text-align: center;">
                        <div style="margin-bottom: 8px; font-size: 16px; font-weight: 800; color: #0f172a;">Select a clinic to preview its question set</div>
                        <div style="font-size: 14px; line-height: 1.7; color: #64748b;">
                            Use the clinic filter in the managed question bank below. Once a clinic is selected, this section will show the current questions for that clinic only.
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div>
                    <h3 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">Managed Template Questions</h3>
                    <p style="margin: 8px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                    Add reusable questions to the built-in sections. These will appear in the verification console under their assigned section.
                    </p>
                </div>
                <div>
                    @foreach ($this->getVisibleHeaderActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </div>
            <div style="padding: 8px 0 0;">
                {{ $this->table }}
            </div>
        </section>
    </div>
    </x-verification-management-shell>
</x-filament-panels::page>
