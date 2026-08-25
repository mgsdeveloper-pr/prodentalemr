<x-filament-panels::page>
    <div style="display:grid;gap:20px;">
        <form wire:submit="importInsurance" style="display:grid;gap:16px;">
            {{ $this->form }}

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <x-filament::button type="button" color="gray" icon="heroicon-o-eye" wire:click="previewImport" wire:loading.attr="disabled" wire:target="previewImport">
                    <span wire:loading.remove wire:target="previewImport">Preview Import</span>
                    <span wire:loading wire:target="previewImport">Checking...</span>
                </x-filament::button>
                <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled" wire:target="importInsurance">
                    <span wire:loading.remove wire:target="importInsurance">Import Insurance</span>
                    <span wire:loading wire:target="importInsurance">Importing...</span>
                </x-filament::button>
            </div>
        </form>

        @if ($previewResult)
            <section style="overflow:hidden;border:1px solid #dbe4ee;border-radius:8px;background:#fff;">
                <div style="padding:18px 20px;border-bottom:1px solid #e5e7eb;">
                    <h2 style="margin:0;font-size:18px;font-weight:800;color:#0f172a;">Import Review</h2>
                    <p style="margin:5px 0 0;font-size:13px;color:#64748b;">New {{ $previewResult['created'] ?? 0 }} · Updates {{ $previewResult['updated'] ?? 0 }} · Unchanged {{ $previewResult['unchanged'] ?? 0 }} · Failed {{ $previewResult['failed'] ?? 0 }}</p>
                </div>
                <div style="overflow:auto;max-height:420px;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead style="position:sticky;top:0;background:#f8fafc;color:#475569;">
                            <tr>
                                <th style="padding:12px 16px;text-align:left;">Row</th>
                                <th style="padding:12px 16px;text-align:left;">Insurance</th>
                                <th style="padding:12px 16px;text-align:left;">Payer ID</th>
                                <th style="padding:12px 16px;text-align:left;">Result</th>
                                <th style="padding:12px 16px;text-align:left;">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewResult['rows'] ?? [] as $row)
                                <tr style="border-top:1px solid #eef2f7;">
                                    <td style="padding:12px 16px;">{{ $row['row'] }}</td>
                                    <td style="padding:12px 16px;font-weight:700;color:#0f172a;">{{ $row['insurance_name'] ?: 'Missing' }}</td>
                                    <td style="padding:12px 16px;">{{ $row['payer_id'] ?: '—' }}</td>
                                    <td style="padding:12px 16px;text-transform:capitalize;">{{ $row['status'] }}</td>
                                    <td style="padding:12px 16px;color:#64748b;">{{ $row['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
