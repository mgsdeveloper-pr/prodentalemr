<x-filament-panels::page>
    <style>
        .ti-layout { display:grid; gap:20px; font-size:var(--pwdl-font-size-body, .875rem); }
        .ti-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; }
        .ti-actions { display:flex; flex-wrap:wrap; gap:12px; align-items:center; }
        .ti-fields { display:grid; grid-template-columns:1fr 1fr; gap:20px; padding-block:20px; border-block:1px solid #dbe4ee; }
        .ti-fields label { display:grid; gap:8px; font-weight:600; }
        .ti-input { width:100%; min-height:40px; border:1px solid #cfd9e6; border-radius:6px; padding:8px 12px; background:var(--fi-body-bg, white); }
        .ti-error { color:#b42318; }
        .ti-table { overflow:auto; border:1px solid #dbe4ee; border-radius:8px; max-height:480px; }
        .ti-table table { width:100%; border-collapse:collapse; text-align:left; }
        .ti-table th,.ti-table td { padding:12px; border-bottom:1px solid #dbe4ee; min-width:110px; overflow-wrap:anywhere; }
        .ti-table th { background:#f8fafc; color:#334155; position:sticky; top:0; }
        @media(max-width:700px) { .ti-fields { grid-template-columns:1fr; } }
    </style>
    <div class="ti-layout">
        <div class="ti-toolbar">
            <strong>{{ $this->scopeLabel() }}</strong>
            <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadSample">Download Sample</x-filament::button>
            <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadV2Sample">Download V2 Sample</x-filament::button>
            <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadV3Sample">Download Compound Answer Sample</x-filament::button>
        </div>
        @if ($createdVersionId)
            <x-filament::section heading="Draft Created">
                <p>{{ $templateName }} is saved as a new draft. Published templates and existing verification answers are unchanged.</p>
                <div class="ti-actions" style="margin-top:16px">
                    <x-filament::button tag="a" :href="$this->builderUrl()" icon="heroicon-o-pencil-square">Open Template Builder</x-filament::button>
                    <x-filament::button color="gray" wire:click="exportReviewed" icon="heroicon-o-arrow-down-tray">Export Imported Template</x-filament::button>
                </div>
            </x-filament::section>
        @else
            <div class="ti-fields">
                <label>Form type
                    <select class="ti-input" wire:model.live="formType">
                        <option value="">Select form type</option>
                        <option value="short_form">Short Form</option>
                        <option value="full_form">Full Form</option>
                    </select>
                    @error('formType')<span class="ti-error">{{ $message }}</span>@enderror
                </label>
                <label>Draft name
                    <input class="ti-input" type="text" wire:model="templateName" maxlength="255">
                    @error('templateName')<span class="ti-error">{{ $message }}</span>@enderror
                </label>
                <label>Template file
                    <input class="ti-input" type="file" wire:model="upload" accept=".xlsx,.csv">
                    <span wire:loading wire:target="upload">Uploading...</span>
                    @error('upload')<span class="ti-error">{{ $message }}</span>@enderror
                </label>
            </div>
            <div class="ti-actions">
                <x-filament::button wire:click="preview" wire:loading.attr="disabled" icon="heroicon-o-eye">Preview Import</x-filament::button>
                <x-filament::button tag="a" :href="$this->builderUrl()" color="gray">Cancel</x-filament::button>
            </div>
        @endif
        @if ($sourceRows && ! $createdVersionId)
            <h2>Review Field Mapping</h2>
            <div class="ti-table">
                <table>
                    <thead><tr><th>Row</th><th>Uploaded question</th><th>Answer destination</th></tr></thead>
                    <tbody>
                    @foreach ($sourceRows as $index => $source)
                        <tr>
                            <td>{{ $source['row'] ?? $index + 2 }}</td>
                            <td>{{ $source['question'] }}<br><small>{{ $source['answer_type'] }}</small>
                            @if (isset($source['format_version']))
                                <div>{{ $source['section_name'] }} @if(filled($source['subsection_name'])) / {{ $source['subsection_name'] }} @endif</div>
                                @foreach (\App\Support\VerificationProcedureTags::inspect($source)['tags'] as $tag)
                                    <div>{{ $tag['system'] }} {{ $tag['code'] }}: {{ $tag['status'] === 'directory_match' ? 'Directory match' : 'Unverified' }} @if($tag['source_year']) ({{ $tag['source_year'] }}) @endif</div>
                                @endforeach
                            @endif
                            </td>
                            <td><select class="ti-input" aria-label="Mapping for row {{ $index + 2 }}" wire:model.live="mappings.{{ $index }}">
                                <option value="">Needs review</option>
                                @unless (\App\Support\VerificationTemplateImport::mappedField($source))
                                    <option value="custom">Custom question - separate verifier answer</option>
                                @endunless
                                @foreach (\App\Support\VerificationTemplateImport::mappingOptions(($source['format_version'] ?? '') === '3') as $key => $field)
                                    <option value="{{ $key }}">{{ $field['prompt'] }} ({{ $field['input_type'] }})</option>
                                @endforeach
                            </select></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div><x-filament::button color="gray" wire:click="validateMappings" icon="heroicon-o-check-circle">Validate Mappings</x-filament::button></div>
        @endif
        @if ($review)
            @if (count($review['errors']))
                <div role="alert" class="ti-error">
                    <strong>Import needs correction ({{ count($review['errors']) }})</strong>
                    <ul>@foreach ($review['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @else
                <div class="ti-toolbar">
                    <strong>{{ count($review['rows']) }} questions validated</strong>
                    @unless ($createdVersionId)
                        <label><input type="checkbox" wire:model="confirmed"> I approve the structure, mappings and code tags for {{ $formType === 'short_form' ? 'Short Form' : 'Full Form' }} at {{ $this->scopeLabel() }}.</label>
                        @error('confirmed')<span class="ti-error">{{ $message }}</span>@enderror
                        <x-filament::button wire:click="importDraft" wire:loading.attr="disabled" icon="heroicon-o-document-plus">Save Draft</x-filament::button>
                    @endunless
                </div>
            @endif
            <div class="ti-table">
                <table>
                    <thead><tr><th>Row</th><th>Section</th><th>Question Key</th><th>Question</th><th>Answer Type</th><th>Audit Required</th><th>Choices</th><th>Form</th><th>Mapping</th></tr></thead>
                    <tbody>@foreach ($review['rows'] as $row)
                        <tr><td>{{ $row['row'] }}</td>
                        @foreach (\App\Support\VerificationTemplateImport::HEADERS as $key)<td>{{ $row[$key] }}</td>@endforeach
                        <td>@if ($mapping = \App\Support\VerificationTemplateImport::mappedField($row))
                            Mapped: {{ $mapping['prompt'] }} ({{ $mapping['field_key'] }}, {{ $mapping['input_type'] }})
                        @else
                            Custom question
                        @endif</td>
                        </tr>
                    @endforeach</tbody>
                </table>
            </div>
        @endif
    </div>
    @if ($this->recentImports()->isNotEmpty())
        <section class="ti-table" aria-label="Recent imports">
            <h2>Your Recent Imports</h2>
            <table>
                <thead><tr><th>Imported</th><th>Template</th><th>Form</th><th>Questions</th><th>Draft ID</th></tr></thead>
                <tbody>@foreach ($this->recentImports() as $receipt)
                    <tr><td>{{ $receipt->created_at->format('M d, Y H:i') }}</td><td>{{ $receipt->name }}</td>
                        <td>{{ \App\Models\VerificationTemplateVersion::FORM_TYPE_OPTIONS[$receipt->form_type] ?? $receipt->form_type }}</td>
                        <td>{{ $receipt->question_count }}</td><td>{{ $receipt->template_version_id ?? 'Deleted' }}</td></tr>
                @endforeach</tbody>
            </table>
        </section>
    @endif
</x-filament-panels::page>
