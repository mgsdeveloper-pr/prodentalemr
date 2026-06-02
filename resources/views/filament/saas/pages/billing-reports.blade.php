<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php
        $summaryCards = $this->getSummaryCards();
        $trendChart = $this->getTrendChart();
        $invoiceStatusVisualization = $this->getInvoiceStatusVisualization();
        $paymentMethodVisualization = $this->getPaymentMethodVisualization();
        $invoiceStatusBreakdown = $this->getInvoiceStatusBreakdown();
        $paymentMethodBreakdown = $this->getPaymentMethodBreakdown();
        $recentInvoices = $this->getRecentInvoices();
        $recentPayments = $this->getRecentPayments();
    @endphp

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950">{{ $card['value'] }}</p>
                <p class="mt-2 text-sm text-gray-500">{{ $card['description'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[2fr,1fr]">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-950">Revenue Trend</h3>
                    <p class="mt-1 text-sm text-gray-500">Invoiced versus collected values across the selected reporting period.</p>
                </div>
                <div class="text-right text-sm">
                    <p class="text-gray-500">Invoiced</p>
                    <p class="font-semibold text-amber-600">${{ number_format($trendChart['total_invoiced'], 2) }}</p>
                    <p class="mt-2 text-gray-500">Collected</p>
                    <p class="font-semibold text-emerald-600">${{ number_format($trendChart['total_collected'], 2) }}</p>
                </div>
            </div>
            <div class="mt-5 overflow-x-auto">
                <svg viewBox="0 0 640 220" class="h-64 w-full min-w-[640px]">
                    <line x1="20" y1="200" x2="620" y2="200" stroke="#e5e7eb" stroke-width="1" />
                    <line x1="20" y1="20" x2="20" y2="200" stroke="#e5e7eb" stroke-width="1" />
                    <polyline
                        fill="none"
                        stroke="#f59e0b"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        points="{{ $trendChart['invoiced_points'] }}"
                    />
                    <polyline
                        fill="none"
                        stroke="#16a34a"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        points="{{ $trendChart['collected_points'] }}"
                    />
                </svg>
            </div>
            <div class="mt-3 flex flex-wrap gap-5 text-sm text-gray-600">
                <span class="inline-flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Invoiced
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                    Collected
                </span>
                <span class="text-gray-500">Peak day: ${{ number_format($trendChart['max'], 2) }}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-500 md:grid-cols-4 xl:grid-cols-6">
                @foreach ($trendChart['labels'] as $index => $label)
                    @if ($loop->first || $loop->last || $index % max((int) ceil(count($trendChart['labels']) / 6), 1) === 0)
                        <span>{{ $label }}</span>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-950">Visual Mix</h3>
            <p class="mt-1 text-sm text-gray-500">Quick at-a-glance distribution for invoices and payments.</p>

            <div class="mt-5">
                <h4 class="text-sm font-medium text-gray-800">Invoice Status</h4>
                <div class="mt-3 space-y-3">
                    @forelse ($invoiceStatusVisualization as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm text-gray-700">
                                <span class="capitalize">{{ $row['status'] }}</span>
                                <span>{{ number_format($row['total']) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full bg-amber-500" style="width: {{ $row['width'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No invoice data available.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-800">Payment Methods</h4>
                <div class="mt-3 space-y-3">
                    @forelse ($paymentMethodVisualization as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm text-gray-700">
                                <span>{{ $row['method'] }}</span>
                                <span>${{ number_format($row['amount'], 2) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full bg-emerald-600" style="width: {{ $row['width'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No payment data available.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-950">Invoice Status Breakdown</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium">Invoices</th>
                            <th class="py-2 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoiceStatusBreakdown as $row)
                            <tr>
                                <td class="py-2 pr-4 capitalize text-gray-800">{{ $row['status'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">{{ number_format($row['total']) }}</td>
                                <td class="py-2 text-gray-800">${{ number_format($row['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-gray-500">No invoice data matches the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-950">Payment Method Breakdown</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4 font-medium">Method</th>
                            <th class="py-2 pr-4 font-medium">Payments</th>
                            <th class="py-2 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($paymentMethodBreakdown as $row)
                            <tr>
                                <td class="py-2 pr-4 text-gray-800">{{ $row['method'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">{{ number_format($row['total']) }}</td>
                                <td class="py-2 text-gray-800">${{ number_format($row['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-gray-500">No payment data matches the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-950">Recent Invoices</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4 font-medium">Invoice</th>
                            <th class="py-2 pr-4 font-medium">Organization</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium">Issued</th>
                            <th class="py-2 pr-4 font-medium">Total</th>
                            <th class="py-2 font-medium">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentInvoices as $invoice)
                            <tr>
                                <td class="py-2 pr-4 text-gray-800">{{ $invoice['invoice_number'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">{{ $invoice['organization'] }}</td>
                                <td class="py-2 pr-4 capitalize text-gray-800">{{ $invoice['status'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">{{ $invoice['issue_date'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">${{ number_format($invoice['total_amount'], 2) }}</td>
                                <td class="py-2 text-gray-800">${{ number_format($invoice['balance_due'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-500">No invoices match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-950">Recent Payments</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4 font-medium">Date</th>
                            <th class="py-2 pr-4 font-medium">Invoice</th>
                            <th class="py-2 pr-4 font-medium">Organization</th>
                            <th class="py-2 pr-4 font-medium">Method</th>
                            <th class="py-2 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentPayments as $payment)
                            <tr>
                                <td class="py-2 pr-4 text-gray-800">{{ $payment['payment_date'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">{{ $payment['invoice_number'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">{{ $payment['organization'] }}</td>
                                <td class="py-2 pr-4 text-gray-800">{{ $payment['method'] }}</td>
                                <td class="py-2 text-gray-800">${{ number_format($payment['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-gray-500">No payments match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
