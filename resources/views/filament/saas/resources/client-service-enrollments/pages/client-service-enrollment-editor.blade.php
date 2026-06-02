<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(135deg, #fffdfa 0%, #ffffff 48%, #f8fafc 100%); box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 24px 28px; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr); gap: 24px; align-items: start;">
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; width: max-content; padding: 8px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #b45309; font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">
                        Service Enrollment
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <h2 style="margin: 0; font-size: 30px; line-height: 1.1; font-weight: 800; color: #0f172a;">{{ $this->getEditorHeading() }}</h2>
                        <p style="margin: 0; max-width: 760px; font-size: 15px; line-height: 1.75; color: #64748b;">
                            {{ $this->getEditorDescription() }}
                        </p>
                    </div>
                </div>

                <div style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; padding: 20px 22px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05); display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #94a3b8;">Workspace mode</div>
                            <div style="margin-top: 6px; font-size: 20px; font-weight: 800; color: #0f172a;">{{ $this->getWorkspaceModeLabel() }}</div>
                        </div>
                        <span style="display: inline-flex; align-items: center; padding: 8px 12px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;">
                            {{ $this->getSubmitButtonLabel() }}
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Organization</div>
                            <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentOrganizationLabel() }}</div>
                        </div>
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Clinic</div>
                            <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentClinicLabel() }}</div>
                        </div>
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Service</div>
                            <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentServiceLabel() }}</div>
                        </div>
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Status</div>
                            <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentStatusLabel() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <form wire:submit="{{ $this->getSubmitMethodName() }}" style="display: flex; flex-direction: column; gap: 22px;">
            <div style="display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr); gap: 22px; align-items: start;">
                <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06); overflow: hidden;">
                    <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Enrollment Configuration</div>
                        <div style="margin-top: 6px; font-size: 15px; line-height: 1.7; color: #64748b;">Define the client scope, service coverage, dates, and operational notes in one clean workspace.</div>
                    </div>
                    <div style="padding: 22px;">
                        {{ $this->form }}
                    </div>
                    <div style="padding: 0 22px 22px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                        <a
                            href="{{ $this->getCancelUrl() }}"
                            style="display: inline-flex; align-items: center; justify-content: center; min-width: 128px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 700; text-decoration: none;"
                        >
                            Cancel
                        </a>
                        <button
                            type="submit"
                            style="display: inline-flex; align-items: center; justify-content: center; min-width: 168px; padding: 11px 18px; border: 0; border-radius: 14px; background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); color: #ffffff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 10px 22px rgba(245, 158, 11, 0.24);"
                        >
                            {{ $this->getSubmitButtonLabel() }}
                        </button>
                    </div>
                </section>

                <div style="display: flex; flex-direction: column; gap: 22px;">
                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05); overflow: hidden;">
                        <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Coverage Summary</div>
                            <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">Confirm the service scope before saving</h3>
                        </div>
                        <div style="padding: 18px 22px; display: grid; gap: 12px;">
                            <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                                <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Location</div>
                                <div style="margin-top: 8px; font-size: 15px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentLocationLabel() }}</div>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                                <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                                    <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Normal SLA</div>
                                    <div style="margin-top: 8px; font-size: 15px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentNormalSlaLabel() }}</div>
                                </div>
                                <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                                    <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Urgent SLA</div>
                                    <div style="margin-top: 8px; font-size: 15px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentUrgentSlaLabel() }}</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05); overflow: hidden;">
                        <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Quick Guidance</div>
                        </div>
                        <div style="padding: 18px 22px; display: flex; flex-direction: column; gap: 12px; font-size: 14px; line-height: 1.75; color: #64748b;">
                            <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #e2e8f0; background: #ffffff;">
                                Use <span style="font-weight: 700; color: #0f172a;">Organization</span> to define the client umbrella, then narrow to a clinic or location only when the service coverage really differs.
                            </div>
                            <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #e2e8f0; background: #ffffff;">
                                Turn on <span style="font-weight: 700; color: #0f172a;">Clinic Workspace Enabled</span> only when clinic teams should collaborate directly with the verification service team.
                            </div>
                            <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #e2e8f0; background: #ffffff;">
                                Keep <span style="font-weight: 700; color: #0f172a;">Notes</span> operational: document exceptions, launch timing, or service boundaries that matter after activation.
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>
