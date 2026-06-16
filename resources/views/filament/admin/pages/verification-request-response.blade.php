<x-filament-panels::page>
    @php($rows = $this->getRows())
    @php($summary = $this->getSummary())
    @php($selectedWorkItem = $this->getSelectedWorkItem())

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08); overflow: hidden;">
            <div style="padding: 24px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                            Verification Flow
                        </div>
                        <div>
                            <h2 style="margin: 0; font-size: 32px; font-weight: 800; color: #0f172a;">Request &amp; Response</h2>
                            <p style="margin: 10px 0 0; max-width: 920px; font-size: 15px; line-height: 1.75; color: #64748b;">
                                Review every information request sent to clinics and every response received back in one clean operational log.
                            </p>
                            <p style="margin: 8px 0 0; font-size: 13px; font-weight: 700; color: #0f172a;">
                                Scope: {{ \App\Support\AdminClinicScope::selectedClinic()?->clinic_name ?? 'All accessible clinics' }}
                            </p>
                        </div>
                    </div>

                    <div x-data="{ open: false }" style="position: relative;">
                        <button
                            type="button"
                            x-on:click="open = ! open"
                            x-on:click.outside="open = false"
                            style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 18px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);"
                        >
                            <span style="display: inline-flex; flex-direction: column; gap: 4px;">
                                <span style="display: block; width: 16px; height: 2px; border-radius: 999px; background: currentColor;"></span>
                                <span style="display: block; width: 16px; height: 2px; border-radius: 999px; background: currentColor;"></span>
                                <span style="display: block; width: 16px; height: 2px; border-radius: 999px; background: currentColor;"></span>
                            </span>
                        </button>

                        <div
                            x-show="open"
                            x-transition
                            style="position: absolute; right: 0; top: calc(100% + 10px); min-width: 180px; padding: 10px; border-radius: 18px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14); z-index: 20;"
                        >
                            <a
                                href="{{ route('admin.verification-request-response.export', ['status' => $this->statusFilter, 'search' => $this->search]) }}"
                                x-on:click="open = false"
                                style="display: inline-flex; align-items: center; gap: 10px; width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-decoration: none;"
                            >
                                <x-heroicon-o-arrow-down-tray style="width: 16px; height: 16px;" />
                                <span>Export</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px;">
            @foreach ($summary as $card)
                <button
                    type="button"
                    wire:click="selectStatusFilter('{{ $card['filter'] }}')"
                    style="padding: 18px 18px 16px; border-radius: 22px; border: 1px solid {{ $card['styles']['border'] }}; background: {{ $card['styles']['bg'] }}; box-shadow: {{ $card['shadow'] }}; text-align: left; outline: none; position: relative;"
                >
                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">{{ $card['label'] }}</div>
                        <span style="display: {{ $card['active_display'] }}; align-items: center; justify-content: center; padding: 3px 8px; border-radius: 999px; background: #ffffff; color: {{ $card['styles']['text'] }}; font-size: 10px; font-weight: 800; border: 1px solid {{ $card['styles']['border'] }};">Active</span>
                    </div>
                    <div style="margin-top: 10px; font-size: 32px; line-height: 1; font-weight: 800; color: {{ $card['styles']['text'] }};">{{ number_format($card['count']) }}</div>
                    <div style="margin-top: 10px; font-size: 12px; color: #64748b;">Click to filter log</div>
                </button>
            @endforeach
        </section>

        <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: #ffffff; box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07); overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <h3 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">Activity Log</h3>
                    <p style="margin: 6px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">One line per verification request so the raised request and received response stay together.</p>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <select wire:model.live="statusFilter" style="padding: 11px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; min-width: 150px;">
                        <option value="all">All items</option>
                        <option value="open">Open requests</option>
                        <option value="responded">Responded items</option>
                        <option value="closed">Closed items</option>
                    </select>

                    <div style="position: relative; min-width: 280px;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                            <x-heroicon-o-magnifying-glass style="width: 18px; height: 18px;" />
                        </span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search patient, clinic, request, or response"
                            style="width: 100%; padding: 12px 14px 12px 42px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;"
                        >
                    </div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 1120px;">
                    <thead>
                        <tr style="background: #f8fbff;">
                            <th style="padding: 16px 18px; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Sr#</th>
                            <th style="padding: 16px 18px; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Patient Name</th>
                            <th style="padding: 16px 18px; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Request Raised</th>
                            <th style="padding: 16px 18px; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Response Received</th>
                            <th style="padding: 16px 18px; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Date &amp; Time</th>
                            <th style="padding: 16px 18px; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Status</th>
                            <th style="padding: 16px 18px; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $workItem)
                            @php($row = $this->presentRow($workItem))
                            <tr style="border-top: 1px solid #edf2f7;">
                                <td style="padding: 18px; font-size: 14px; font-weight: 700; color: #0f172a;">{{ (($rows->currentPage() - 1) * $rows->perPage()) + $index + 1 }}</td>
                                <td style="padding: 18px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <div style="font-size: 15px; font-weight: 800; color: #0f172a;">{{ $row['patient_name'] }}</div>
                                        <div style="font-size: 12px; color: #64748b;">{{ $workItem->reference_number }} · {{ $row['clinic_name'] }}</div>
                                    </div>
                                </td>
                                <td style="padding: 18px;">
                                    <div style="font-size: 14px; line-height: 1.7; color: #334155;">{{ $row['request_raised'] }}</div>
                                    <div style="margin-top: 6px; font-size: 12px; color: #94a3b8;">{{ $row['request_count'] }} request{{ $row['request_count'] === 1 ? '' : 's' }}</div>
                                </td>
                                <td style="padding: 18px;">
                                    <div style="font-size: 14px; line-height: 1.7; color: #334155;">{{ $row['response_received'] }}</div>
                                    <div style="margin-top: 6px; font-size: 12px; color: #94a3b8;">{{ $row['response_count'] }} response{{ $row['response_count'] === 1 ? '' : 's' }}</div>
                                </td>
                                <td style="padding: 18px; font-size: 14px; color: #334155;">{{ $row['date_time'] }}</td>
                                <td style="padding: 18px;">
                                    <span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid {{ $row['status_styles']['border'] }}; background: {{ $row['status_styles']['bg'] }}; color: {{ $row['status_styles']['text'] }}; font-size: 12px; font-weight: 800;">
                                        <span style="width: 8px; height: 8px; border-radius: 999px; background: currentColor;"></span>
                                        {{ $row['status']['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 18px;">
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <button
                                            type="button"
                                            wire:click="openDetails({{ $workItem->getKey() }})"
                                            style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700;"
                                        >
                                            <x-heroicon-o-eye style="width: 16px; height: 16px;" />
                                            <span>View</span>
                                        </button>

                                        <a
                                            href="{{ $this->openWorkItemUrl($workItem) }}"
                                            wire:navigate
                                            style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 12px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 700; text-decoration: none;"
                                        >
                                            <x-heroicon-o-arrow-top-right-on-square style="width: 16px; height: 16px;" />
                                            <span>Open</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 56px 24px;">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 14px; text-align: center;">
                                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 68px; height: 68px; border-radius: 22px; background: #f8fbff; border: 1px solid #dbe4ee; color: #64748b;">
                                            <x-heroicon-o-chat-bubble-left-right style="width: 28px; height: 28px;" />
                                        </div>
                                        <div style="font-size: 20px; font-weight: 800; color: #0f172a;">No request activity found</div>
                                        <div style="max-width: 560px; color: #64748b; font-size: 14px; line-height: 1.8;">
                                            No request and response activity matches the current clinic scope and filters. Open a verification and raise a request to start tracking it here.
                                        </div>
                                        <a
                                            href="{{ \App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource::getUrl('index') }}"
                                            wire:navigate
                                            style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 15px; border-radius: 14px; border: 1px solid #fbbf24; background: #fbbf24; color: #0f172a; font-size: 13px; font-weight: 800; text-decoration: none;"
                                        >
                                            <x-heroicon-o-plus style="width: 16px; height: 16px;" />
                                            <span>Raise Request</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="padding: 18px 22px; border-top: 1px solid #edf2f7;">
                {{ $rows->links() }}
            </div>
        </section>
    </div>

    @if ($showDetailsModal && $selectedWorkItem)
        @php($requestHistory = $this->getRequestHistory($selectedWorkItem))
        @php($responseHistory = $this->getResponseHistory($selectedWorkItem))
        @php($responseAttachments = $this->getResponseAttachments($selectedWorkItem))

        <div style="position: fixed; inset: 0; z-index: 80; background: rgba(15, 23, 42, 0.42); display: flex; align-items: center; justify-content: center; padding: 24px;">
            <div style="display: flex; flex-direction: column; width: min(1200px, 100%); max-height: calc(100vh - 48px); border-radius: 28px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 30px 80px rgba(15, 23, 42, 0.26); overflow: hidden;">
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 18px;">
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                            {{ $selectedWorkItem->reference_number }}
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 28px; font-weight: 800; color: #0f172a;">{{ $selectedWorkItem->verificationProfile?->patient_full_name ?: ($selectedWorkItem->patient?->full_name ?: 'Unknown patient') }}</h3>
                            <p style="margin: 8px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                                {{ $selectedWorkItem->clinic?->clinic_name ?: '-' }} · {{ \App\Models\BillingWorkItem::STATUS_OPTIONS[$selectedWorkItem->normalized_status] ?? str($selectedWorkItem->normalized_status)->headline()->toString() }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closeDetails"
                        style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155;"
                    >
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                    </button>
                </div>

                <div style="flex: 1 1 auto; overflow: auto; padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px;">
                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Request History</h4>
                        </div>
                        <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px; height: 430px; overflow: auto;">
                            @forelse ($requestHistory as $item)
                                @php($messageText = filled($item['message']) ? $item['message'] : $item['message_fallback'])
                                @php($showTitle = filled($item['title']) && trim(mb_strtolower($item['title'])) !== trim(mb_strtolower($messageText)))
                                <div style="height: 190px; min-height: 190px; flex: 0 0 190px; padding: 16px; border-radius: 18px; border: 1px solid #fde68a; background: #fffbeb; display: flex; flex-direction: column; overflow: hidden;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #92400e; font-size: 10px; font-weight: 800; border: 1px solid #fcd34d;">{{ $item['source_label'] }}</span>
                                        <span style="font-size: 12px; color: #a16207;">to</span>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #92400e; font-size: 10px; font-weight: 800; border: 1px solid #fcd34d;">{{ $item['target_label'] }}</span>
                                    </div>
                                    @if ($showTitle)
                                        <div style="font-size: 15px; line-height: 1.5; color: #0f172a; font-weight: 700; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $item['title'] }}</div>
                                    @endif
                                    <div style="margin-top: {{ $showTitle ? '12px' : '4px' }}; display: flex; flex: 1 1 auto; flex-direction: column; gap: 6px; min-height: 0;">
                                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #92400e;">{{ $item['message_label'] }}</div>
                                        <div style="font-size: 14px; line-height: 1.7; color: #475569; display: -webkit-box; -webkit-line-clamp: {{ $showTitle ? '3' : '4' }}; -webkit-box-orient: vertical; overflow: hidden;">{{ $messageText }}</div>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 12px; color: #64748b;">{{ $item['actor'] }} · {{ $item['role'] }} · {{ $item['date'] }}</div>
                                </div>
                            @empty
                                <div style="padding: 10px 0; font-size: 14px; line-height: 1.7; color: #64748b;">No request history has been logged for this verification yet.</div>
                            @endforelse
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Response History</h4>
                        </div>
                        <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px; height: 430px; overflow: auto;">
                            @forelse ($responseHistory as $item)
                                @php($messageText = filled($item['message']) ? $item['message'] : $item['message_fallback'])
                                @php($showTitle = filled($item['title']) && trim(mb_strtolower($item['title'])) !== trim(mb_strtolower($messageText)))
                                <div style="height: 190px; min-height: 190px; flex: 0 0 190px; padding: 16px; border-radius: 18px; border: 1px solid #bfdbfe; background: #f8fbff; display: flex; flex-direction: column; overflow: hidden;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #1d4ed8; font-size: 10px; font-weight: 800; border: 1px solid #bfdbfe;">{{ $item['source_label'] }}</span>
                                        <span style="font-size: 12px; color: #64748b;">to</span>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #1d4ed8; font-size: 10px; font-weight: 800; border: 1px solid #bfdbfe;">{{ $item['target_label'] }}</span>
                                    </div>
                                    @if ($showTitle)
                                        <div style="font-size: 15px; line-height: 1.5; color: #0f172a; font-weight: 700; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $item['title'] }}</div>
                                    @endif
                                    <div style="margin-top: {{ $showTitle ? '12px' : '4px' }}; display: flex; flex: 1 1 auto; flex-direction: column; gap: 6px; min-height: 0;">
                                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #1d4ed8;">{{ $item['message_label'] }}</div>
                                        <div style="font-size: 14px; line-height: 1.7; color: #475569; display: -webkit-box; -webkit-line-clamp: {{ $showTitle ? '3' : '4' }}; -webkit-box-orient: vertical; overflow: hidden;">{{ $messageText }}</div>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 12px; color: #64748b;">{{ $item['actor'] }} · {{ $item['role'] }} · {{ $item['date'] }}</div>
                                </div>
                            @empty
                                <div style="padding: 10px 0; font-size: 14px; line-height: 1.7; color: #64748b;">No response has been received for this verification yet.</div>
                            @endforelse
                        </div>
                    </section>
                    </div>

                    @if ($responseAttachments->isNotEmpty())
                        <div style="margin-top: 20px;">
                        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; overflow: hidden;">
                            <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                                <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Response Attachments</h4>
                            </div>
                            <div style="padding: 18px; display: flex; flex-wrap: wrap; gap: 12px;">
                                @foreach ($responseAttachments as $attachment)
                                    <a
                                        href="{{ route('saas.billing-work-item-attachments.download', $attachment) }}"
                                        style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-decoration: none;"
                                    >
                                        <x-heroicon-o-paper-clip style="width: 16px; height: 16px;" />
                                        <span>{{ $attachment->original_file_name ?: $attachment->title ?: 'Attachment' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                        </div>
                    @endif
                </div>

                <div style="padding: 18px 24px 20px; border-top: 1px solid #edf2f7; display: flex; align-items: center; justify-content: flex-end; gap: 12px; background: #ffffff;">
                    <button
                        type="button"
                        wire:click="closeDetails"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700;"
                    >
                        Close
                    </button>
                    @if ($this->canShowRequestShortcut($selectedWorkItem))
                        <button
                            type="button"
                            wire:click="openRequestComposer({{ $selectedWorkItem->getKey() }})"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 14px; font-weight: 700; text-decoration: none;"
                        >
                            {{ $this->requestActionLabel($selectedWorkItem) }}
                        </button>
                    @endif
                    <a
                        href="{{ $this->openWorkItemUrl($selectedWorkItem) }}"
                        wire:navigate
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 14px; border: 1px solid #0f766e; background: linear-gradient(180deg, #14b8a6 0%, #0f766e 100%); color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none;"
                    >
                        Open Verification
                    </a>
                </div>

            </div>
        </div>
    @endif

    @if ($showRequestComposerModal && $selectedWorkItem)
        <div id="verification-request-composer-modal" style="position: fixed; inset: 0; z-index: 90; background: rgba(15, 23, 42, 0.22); display: flex; align-items: center; justify-content: center; padding: 24px;">
            <div style="position: relative; width: min(720px, 100%); border-radius: 26px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #1d4ed8;">Verification Follow-Up</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $this->requestActionLabel($selectedWorkItem) }}</h3>
                        <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Ask the clinic exactly what is missing so the verification team can continue without leaving this workflow.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeRequestComposer"
                        wire:loading.attr="disabled"
                        wire:target="sendRequestToClinic"
                        style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px;"
                    >
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                    </button>
                </div>

                <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                        <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fbff;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Patient</div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $selectedWorkItem->verificationProfile?->patient_full_name ?: ($selectedWorkItem->patient?->full_name ?: 'Unknown patient') }}</div>
                        </div>
                        <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fbff;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Clinic</div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $selectedWorkItem->clinic?->clinic_name ?: '-' }}</div>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">
                            Information Asked
                        </label>
                        <textarea
                            wire:model.live="requestComposerReason"
                            placeholder="Example: Please upload the updated insurance card and confirm the subscriber date of birth before verification can continue."
                            style="width: 100%; min-height: 180px; padding: 14px 16px; border: 1px solid #d6dde8; border-radius: 16px; background: #ffffff; color: #0f172a; font-size: 14px; line-height: 1.7; resize: vertical;"
                        ></textarea>
                        @error('requestComposerReason')
                            <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="padding: 18px 22px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 12px;">
                    <button
                        type="button"
                        wire:click="closeRequestComposer"
                        wire:loading.attr="disabled"
                        wire:target="sendRequestToClinic"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 12px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 800;"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="sendRequestToClinic"
                        wire:loading.attr="disabled"
                        wire:target="sendRequestToClinic"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 172px; padding: 12px 16px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800;"
                    >
                        Send to Clinic
                    </button>
                </div>

                <div
                    wire:loading.flex
                    wire:target="sendRequestToClinic"
                    style="position: absolute; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.82); backdrop-filter: blur(2px); z-index: 5;"
                >
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 24px 28px; border-radius: 22px; border: 1px solid #dbe4ee; background: rgba(255, 255, 255, 0.96); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);">
                        <div style="width: 42px; height: 42px; border-radius: 999px; border: 3px solid #dbeafe; border-top-color: #2563eb; animation: verification-request-spin 0.8s linear infinite;"></div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">Sending request. Please wait...</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        @keyframes verification-request-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1180px) {
            .fi-main section[style*="grid-template-columns: repeat(4"] {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 960px) {
            .fi-main div[style*="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px;"] {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 720px) {
            .fi-main section[style*="grid-template-columns: repeat(4"] {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('verification-request-composer-closed', () => {
                const modal = document.getElementById('verification-request-composer-modal');

                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</x-filament-panels::page>
