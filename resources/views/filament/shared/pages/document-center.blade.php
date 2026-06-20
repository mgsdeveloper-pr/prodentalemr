<x-filament-panels::page>
    @php
        $rows = $this->getRows();
        $stats = $this->getStats();
        $typeOptions = $this->getTypeOptions();
        $panelMode = $this->getPanelMode();
        $selectedDocument = $this->getSelectedDocument();
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px;">
        <section style="border:1px solid #dbe4ee;border-radius:26px;background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%);box-shadow:0 18px 40px rgba(15,23,42,0.06);padding:26px 28px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;">
                <div>
                    <div style="display:inline-flex;align-items:center;width:max-content;padding:8px 12px;border-radius:999px;background:#eef2ff;border:1px solid #c7d2fe;color:#4338ca;font-size:12px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;">
                        Document Control
                    </div>
                    <h2 style="margin:14px 0 0;font-size:32px;line-height:1.08;font-weight:900;color:#0f172a;">Document Center</h2>
                    <p style="margin:10px 0 0;max-width:760px;font-size:15px;line-height:1.7;color:#64748b;">
                        Review uploaded verification files, clinic responses, and patient documents from one clean operational view.
                    </p>
                </div>

                <div style="display:grid;grid-template-columns:repeat(4,minmax(110px,1fr));gap:10px;min-width:min(520px,100%);">
                    <div style="border:1px solid #dbeafe;border-radius:18px;background:#eff6ff;padding:14px;">
                        <div style="font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#1d4ed8;">Total</div>
                        <div style="margin-top:8px;font-size:26px;font-weight:900;color:#1d4ed8;">{{ $stats['total'] }}</div>
                    </div>
                    <div style="border:1px solid #fde68a;border-radius:18px;background:#fffbeb;padding:14px;">
                        <div style="font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#b45309;">Verification</div>
                        <div style="margin-top:8px;font-size:26px;font-weight:900;color:#b45309;">{{ $stats['verification'] }}</div>
                    </div>
                    <div style="border:1px solid #bbf7d0;border-radius:18px;background:#f0fdf4;padding:14px;">
                        <div style="font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#166534;">Patient</div>
                        <div style="margin-top:8px;font-size:26px;font-weight:900;color:#166534;">{{ $stats['patient'] }}</div>
                    </div>
                    <div style="border:1px solid #e2e8f0;border-radius:18px;background:#ffffff;padding:14px;">
                        <div style="font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Storage</div>
                        <div style="margin-top:8px;font-size:22px;font-weight:900;color:#0f172a;">{{ $stats['storage'] }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section style="border:1px solid #dbe4ee;border-radius:26px;background:#ffffff;box-shadow:0 16px 34px rgba(15,23,42,0.06);overflow:hidden;">
            <div style="padding:18px 22px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <h3 style="margin:0;font-size:22px;font-weight:900;color:#0f172a;">Uploaded Documents</h3>
                    <p style="margin:6px 0 0;font-size:13px;color:#64748b;">{{ $rows->count() }} document{{ $rows->count() === 1 ? '' : 's' }} in current view.</p>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <select wire:model.live="typeFilter" style="min-width:170px;border:1px solid #cbd5e1;border-radius:14px;background:#ffffff;padding:10px 12px;font-size:13px;font-weight:700;color:#0f172a;outline:none;">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="dateFilter" style="min-width:145px;border:1px solid #cbd5e1;border-radius:14px;background:#ffffff;padding:10px 12px;font-size:13px;font-weight:700;color:#0f172a;outline:none;">
                        <option value="all">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                    <input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Search patient, file, reference..."
                        style="min-width:280px;border:1px solid #cbd5e1;border-radius:14px;background:#ffffff;padding:10px 12px;font-size:13px;font-weight:600;color:#0f172a;outline:none;"
                    >
                </div>
            </div>

            <div style="overflow:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e5e7eb;">
                            <th style="padding:14px 18px;text-align:left;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Document</th>
                            <th style="padding:14px 18px;text-align:left;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Patient / Clinic</th>
                            <th style="padding:14px 18px;text-align:left;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Source</th>
                            <th style="padding:14px 18px;text-align:left;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Uploaded</th>
                            <th style="padding:14px 18px;text-align:right;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr style="border-bottom:1px solid #edf2f7;">
                                <td style="padding:16px 18px;vertical-align:top;">
                                    <div style="display:flex;flex-direction:column;gap:6px;">
                                        <div style="font-size:14px;font-weight:900;color:#0f172a;">{{ $row['title'] }}</div>
                                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                            <span style="display:inline-flex;padding:5px 8px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:11px;font-weight:900;">{{ $row['type_label'] }}</span>
                                            <span style="font-size:12px;color:#64748b;">{{ $row['file_name'] }}</span>
                                            <span style="font-size:12px;color:#94a3b8;">{{ $row['size_label'] }}</span>
                                            @if (! $row['is_available'])
                                                <span style="display:inline-flex;padding:5px 8px;border-radius:999px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:11px;font-weight:900;">Missing file</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:16px 18px;vertical-align:top;">
                                    <div style="font-size:14px;font-weight:800;color:#0f172a;">{{ $row['patient'] }}</div>
                                    <div style="margin-top:4px;font-size:12px;color:#64748b;">{{ $row['clinic'] }}</div>
                                </td>
                                <td style="padding:16px 18px;vertical-align:top;">
                                    <div style="font-size:13px;font-weight:800;color:#334155;">{{ $row['source'] }}</div>
                                    <div style="margin-top:4px;font-size:12px;color:#64748b;">{{ $panelMode === 'clinic' ? 'Clinic panel' : 'Verification panel' }}</div>
                                </td>
                                <td style="padding:16px 18px;vertical-align:top;">
                                    <div style="font-size:13px;font-weight:800;color:#334155;">{{ $row['uploaded_at'] }}</div>
                                    <div style="margin-top:4px;font-size:12px;color:#64748b;">{{ $row['uploaded_by'] }}</div>
                                </td>
                                <td style="padding:16px 18px;vertical-align:top;text-align:right;">
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                                        @if (($row['preview_url'] ?? null) && $row['is_available'])
                                            <button type="button" wire:click="openPreview('{{ $row['id'] }}')" style="display:inline-flex;align-items:center;justify-content:center;padding:9px 12px;border-radius:12px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:900;text-decoration:none;cursor:pointer;">View</button>
                                        @endif
                                        @if ($row['download_url'] && $row['is_available'])
                                            <a href="{{ $row['download_url'] }}" style="display:inline-flex;align-items:center;justify-content:center;padding:9px 12px;border-radius:12px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:12px;font-weight:900;text-decoration:none;">Download</a>
                                        @endif
                                        @if ($row['related_url'])
                                            <a href="{{ $row['related_url'] }}" style="display:inline-flex;align-items:center;justify-content:center;padding:9px 12px;border-radius:12px;border:0;background:#0f766e;color:#ffffff;font-size:12px;font-weight:900;text-decoration:none;">Open Related</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:42px 18px;text-align:center;color:#64748b;font-size:14px;">
                                    No documents match the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($showPreviewModal && $selectedDocument)
            <div
                wire:click.self="closePreview"
                style="position:fixed;inset:0;z-index:60;background:rgba(15,23,42,0.52);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:28px;"
            >
                <section style="width:min(1120px,96vw);height:min(820px,92vh);border-radius:26px;background:#ffffff;box-shadow:0 28px 80px rgba(15,23,42,0.28);overflow:hidden;display:flex;flex-direction:column;">
                    <div style="padding:18px 22px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;gap:16px;">
                        <div style="min-width:0;">
                            <div style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;">
                                {{ $selectedDocument['type_label'] }}
                            </div>
                            <h3 style="margin:10px 0 0;font-size:24px;font-weight:900;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $selectedDocument['title'] }}</h3>
                            <p style="margin:6px 0 0;font-size:13px;color:#64748b;">
                                {{ $selectedDocument['patient'] }} · {{ $selectedDocument['clinic'] }} · {{ $selectedDocument['uploaded_at'] }}
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
                            @if ($selectedDocument['preview_url'] ?? null)
                                <a href="{{ $selectedDocument['preview_url'] }}" target="_blank" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:14px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:13px;font-weight:900;text-decoration:none;">Open New Tab</a>
                            @endif
                            @if ($selectedDocument['download_url'])
                                <a href="{{ $selectedDocument['download_url'] }}" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:14px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:13px;font-weight:900;text-decoration:none;">Download</a>
                            @endif
                            <button type="button" wire:click="closePreview" style="width:42px;height:42px;border-radius:999px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:22px;line-height:1;cursor:pointer;">&times;</button>
                        </div>
                    </div>

                    <div style="flex:1;background:#f8fafc;padding:18px;min-height:0;">
                        @if (($selectedDocument['preview_url'] ?? null) && $selectedDocument['is_available'])
                            <iframe
                                src="{{ $selectedDocument['preview_url'] }}"
                                title="{{ $selectedDocument['title'] }}"
                                style="width:100%;height:100%;border:1px solid #dbe4ee;border-radius:18px;background:#ffffff;"
                            ></iframe>
                        @else
                            <div style="height:100%;display:flex;align-items:center;justify-content:center;border:1px dashed #cbd5e1;border-radius:18px;background:#ffffff;color:#64748b;font-size:14px;">
                                Preview is not available because the file is missing or inaccessible.
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
