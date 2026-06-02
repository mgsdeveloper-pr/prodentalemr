<x-filament-panels::page>
    <div>
        @php
            $groups = $this->getCheckGroups();
            $recordChecks = $this->getRecordChecks();
            $totalWarnings = $this->getTotalWarnings();
            $moduleWarningCount = $this->getModuleWarningCount();
            $recordWarningCount = $this->getRecordWarningCount();
            $groupCount = count($groups);
        @endphp

        <div style="display: flex; flex-direction: column; gap: 24px;">
            <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 58%, #ffffff 100%); padding: 28px 32px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);">
                <div style="display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 20px; align-items: start;">
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: rgba(255,255,255,0.9); border: 1px solid #fed7aa; color: #b45309; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                            Setup Monitor
                        </div>
                        <h2 style="margin: 18px 0 10px; font-size: 34px; line-height: 1.1; font-weight: 700; color: #111827;">Setup Checks</h2>
                        <p style="margin: 0; max-width: 780px; font-size: 15px; line-height: 1.7; color: #6b7280;">
                            Review missing configuration, billing prerequisites, and module readiness from one dedicated operations board so ongoing work in the SaaS panel stays uninterrupted.
                        </p>
                    </div>

                    <div style="display: grid; gap: 12px;">
                        <div style="border: 1px solid #fde68a; border-radius: 18px; background: rgba(255,255,255,0.92); padding: 16px 18px;">
                            <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Active Warnings</div>
                            <div style="margin-top: 8px; font-size: 32px; font-weight: 700; color: #111827;">{{ $totalWarnings }}</div>
                            <div style="margin-top: 6px; font-size: 13px; color: #6b7280;">Issues currently need attention.</div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;">
                            <div style="border: 1px solid #e5e7eb; border-radius: 18px; background: rgba(255,255,255,0.92); padding: 16px 18px;">
                                <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Module Groups</div>
                                <div style="margin-top: 8px; font-size: 26px; font-weight: 700; color: #111827;">{{ $groupCount }}</div>
                            </div>
                            <div style="border: 1px solid {{ $recordWarningCount > 0 ? '#fca5a5' : '#bfdbfe' }}; border-radius: 18px; background: {{ $recordWarningCount > 0 ? '#fff1f2' : '#eff6ff' }}; padding: 16px 18px;">
                                <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Record Alerts</div>
                                <div style="margin-top: 8px; font-size: 26px; font-weight: 700; color: #111827;">{{ $recordWarningCount }}</div>
                                <div style="margin-top: 6px; font-size: 13px; color: #6b7280;">Data-level issues ready for review.</div>
                            </div>
                            <div style="border: 1px solid {{ $totalWarnings > 0 ? '#fdba74' : '#86efac' }}; border-radius: 18px; background: {{ $totalWarnings > 0 ? '#fff7ed' : '#ecfdf5' }}; padding: 16px 18px;">
                                <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Current Status</div>
                                <div style="margin-top: 8px; font-size: 16px; font-weight: 700; color: {{ $totalWarnings > 0 ? '#b45309' : '#166534' }};">
                                    {{ $totalWarnings > 0 ? 'Needs attention' : 'All clear' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 22px; background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border-bottom: 1px solid #e5e7eb;">
                    <div>
                        <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #111827;">Record Watchlist</h3>
                        <p style="margin: 6px 0 0; font-size: 13px; color: #6b7280;">Concrete record-level checks that need review without interrupting normal billing or admin work.</p>
                    </div>
                    <div style="padding: 8px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; background: {{ $recordWarningCount > 0 ? '#fee2e2' : '#dbeafe' }}; color: {{ $recordWarningCount > 0 ? '#b91c1c' : '#1d4ed8' }};">
                        {{ $recordWarningCount > 0 ? $recordWarningCount . ' record issue' . ($recordWarningCount === 1 ? '' : 's') : 'Watching' }}
                    </div>
                </div>

                @if (count($recordChecks) > 0)
                    <div style="padding: 18px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px;">
                        @foreach ($recordChecks as $check)
                            <article style="border: 1px solid #fecaca; border-radius: 18px; background: #fffaf0; padding: 16px;">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                                    <div>
                                        <div style="font-size: 16px; font-weight: 700; color: #111827;">{{ $check['label'] }}</div>
                                        <div style="margin-top: 6px; font-size: 13px; line-height: 1.6; color: #6b7280;">{{ $check['description'] }}</div>
                                    </div>
                                    <div style="padding: 7px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; background: #fee2e2; color: #b91c1c; white-space: nowrap;">
                                        {{ $check['count'] }} flagged
                                    </div>
                                </div>

                                @if (filled($check['action']['url'] ?? null))
                                    <div style="margin-top: 14px;">
                                        <a href="{{ $check['action']['url'] }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; background: #0f172a; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none;">
                                            <span>{{ $check['action']['label'] ?? 'Review records' }}</span>
                                            <span aria-hidden="true">&#8594;</span>
                                        </a>
                                    </div>
                                @endif

                                <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 10px;">
                                    @foreach ($check['items'] as $issue)
                                        <div style="padding: 12px 14px; border-radius: 14px; background: #ffffff; border: 1px solid #fed7aa;">
                                            <div style="font-size: 13px; font-weight: 700; color: #7c2d12;">{{ $issue['title'] }}</div>
                                            <div style="margin-top: 4px; font-size: 13px; line-height: 1.6; color: #9a3412;">{{ $issue['message'] }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($check['count'] > count($check['items']))
                                    <div style="margin-top: 10px; font-size: 12px; color: #9a3412;">
                                        Showing the latest {{ count($check['items']) }} flagged record{{ count($check['items']) === 1 ? '' : 's' }} here.
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div style="padding: 22px;">
                        <div style="border: 1px dashed #bfdbfe; border-radius: 18px; background: #f8fbff; padding: 18px 20px;">
                            <div style="font-size: 15px; font-weight: 700; color: #0f172a;">No record-specific issues are currently flagged.</div>
                            <div style="margin-top: 6px; font-size: 13px; line-height: 1.6; color: #64748b;">This watchlist is monitoring invoices, subscriptions, and payment summaries separately from general setup readiness.</div>
                        </div>
                    </div>
                @endif
            </section>

            <section style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px;">
                @foreach ($groups as $group)
                    <article style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 22px; background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                            <div>
                                <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #111827;">{{ $group['label'] }}</h3>
                                <p style="margin: 6px 0 0; font-size: 13px; color: #6b7280;">Module readiness and prerequisite checks for this operating area.</p>
                            </div>
                            <div style="padding: 8px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; background: {{ $group['count'] > 0 ? '#fef3c7' : '#dcfce7' }}; color: {{ $group['count'] > 0 ? '#92400e' : '#166534' }};">
                                {{ $group['count'] > 0 ? $group['count'] . ' issue' . ($group['count'] === 1 ? '' : 's') : 'Clear' }}
                            </div>
                        </div>

                        <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px;">
                            @foreach ($group['items'] as $item)
                                <section style="border: 1px solid {{ $item['count'] > 0 ? '#fed7aa' : '#bbf7d0' }}; border-radius: 18px; background: {{ $item['count'] > 0 ? '#fffaf0' : '#f0fdf4' }}; padding: 16px;">
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                                            <div style="width: 38px; height: 38px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; background: {{ $item['count'] > 0 ? '#ffedd5' : '#dcfce7' }}; color: {{ $item['count'] > 0 ? '#c2410c' : '#15803d' }}; flex-shrink: 0;">
                                                @if ($item['count'] > 0)
                                                    <span style="font-size: 18px; font-weight: 700;">!</span>
                                                @else
                                                    <span style="font-size: 18px; font-weight: 700;">&#10003;</span>
                                                @endif
                                            </div>
                                            <div>
                                                <div style="font-size: 16px; font-weight: 700; color: #111827;">{{ $item['label'] }}</div>
                                                <div style="margin-top: 4px; font-size: 13px; color: #6b7280;">
                                                    {{ $item['count'] > 0 ? 'Review the listed setup gaps before expanding this module further.' : 'No active setup issues detected for this module.' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div style="padding: 7px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; background: {{ $item['count'] > 0 ? '#fef3c7' : '#dcfce7' }}; color: {{ $item['count'] > 0 ? '#92400e' : '#166534' }}; white-space: nowrap;">
                                            {{ $item['count'] > 0 ? $item['count'] . ' warning' . ($item['count'] === 1 ? '' : 's') : 'OK' }}
                                        </div>
                                    </div>

                                    @if ($item['count'] > 0 && filled($item['action']['url'] ?? null))
                                        <div style="margin-top: 14px;">
                                            <a href="{{ $item['action']['url'] }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; background: #111827; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none; box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);">
                                                <span>{{ $item['action']['label'] ?? 'Open module' }}</span>
                                                <span aria-hidden="true">&#8594;</span>
                                            </a>
                                        </div>
                                    @endif

                                    @if ($item['count'] > 0)
                                        <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 10px;">
                                            @foreach ($item['warnings'] as $warning)
                                                <div style="display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 14px; background: #ffffff; border: 1px solid #fde68a;">
                                                    <div style="width: 24px; height: 24px; border-radius: 999px; background: #fff7ed; color: #c2410c; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0;">!</div>
                                                    <div style="font-size: 13px; line-height: 1.6; color: #7c2d12;">{{ $warning }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-filament-panels::page>
