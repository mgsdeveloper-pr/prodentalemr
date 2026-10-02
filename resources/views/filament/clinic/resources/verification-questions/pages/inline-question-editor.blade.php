<div class="tb-inline-editor" style="padding:18px;background:#f5faf9;border-block:1px solid #cfe6e3" wire:key="inline-editor-{{ $editingQuestionId ?? 'new' }}">
    <h3 class="tb-toolbar-title">{{ $editingQuestionId ? 'Edit Question' : 'New Question' }}</h3>
    @foreach ($errors->all() as $error)<p role="alert" style="color:#b91c1c">{{ $error }}</p>@endforeach
    <div style="display:grid;gap:14px;margin-top:14px">
        <label class="tb-field">Question<input class="tb-input" wire:model.live.debounce.300ms="editor.prompt"></label>
        <div class="tb-filters" style="padding:0;background:transparent;border:0;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
            <label class="tb-field">Section / Subsection<select class="tb-select" wire:model.live="editor.section_key">
                @foreach ($this->getQuestionSections() as $s)<option value="{{ $s['key'] }}">{{ $s['title'] }}</option>@endforeach
            </select></label>
            <label class="tb-field">Answer Type<select class="tb-select" wire:model.live="editor.input_type">
                @foreach (\App\Models\VerificationFormQuestion::INPUT_TYPE_OPTIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select></label>
            <label class="tb-field">Data Connection<select class="tb-select" wire:model.live="editor.mapping">
                @if (($originalEditor['mapping'] ?? '') === 'keep')<option value="keep">Keep current connection</option>@endif
                <option value="standalone">Standalone clinic answer</option>
                @foreach ($this->getInlineMappings() as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select></label>
        </div>
        @if (($editor['mapping'] ?? '') === 'standalone' && ($originalEditor['mapping'] ?? '') === 'keep')
            <label><input type="checkbox" wire:model.live="editor.confirm_disconnect"> Disconnect from the existing data field. This draft question will store a separate clinic answer.</label>
        @endif
        @if (in_array($editor['input_type'] ?? '', ['select', 'multi_select'], true))
            <label class="tb-field">Answer Choices<textarea class="tb-input" rows="4" wire:model.live.debounce.300ms="editor.select_options"></textarea></label>
        @endif
        @if (($editor['input_type'] ?? '') === 'frequency_row')
            <label class="tb-field">Answer Layout<select class="tb-select" wire:model.live="editor.answer_layout"><option value="">Select</option>@foreach (\App\Models\VerificationFormQuestion::ANSWER_LAYOUT_OPTIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label class="tb-field">Response Mode<select class="tb-select" wire:model.live="editor.frequency_response_mode"><option value="current">Current</option><option value="advanced">Advanced</option></select></label>
            <fieldset><legend>Optional Answer Fields</legend><div style="display:flex;gap:12px;flex-wrap:wrap">
                @foreach (\App\Models\VerificationFormQuestion::FREQUENCY_ADVANCED_OPTIONAL_FIELDS as $key => $label)<label><input type="checkbox" value="{{ $key }}" wire:model.live="editor.frequency_response_fields"> {{ $label }}</label>@endforeach
            </div></fieldset>
        @endif
        <label class="tb-field">Help Text<textarea class="tb-input" wire:model.live.debounce.300ms="editor.help_text"></textarea></label>
        <label class="tb-field">Placeholder<input class="tb-input" wire:model.live.debounce.300ms="editor.placeholder"></label>
        <details><summary>Procedure Tags</summary>
            <label class="tb-field">Code System<select class="tb-select" wire:model.live="editor.code_system"><option value="CDT">ADA / CDT</option><option value="CPT">CPT</option></select></label>
            <label class="tb-field">Codes<input class="tb-input" placeholder="D0120|D0140" wire:model.live.debounce.300ms="editor.procedure_codes"></label>
        </details>
        <div style="display:flex;gap:16px;flex-wrap:wrap">
            <label><input type="checkbox" wire:model.live="editor.is_active"> Active</label>
            <label><input type="checkbox" wire:model.live="editor.is_required_for_audit"> Required for Audit</label>
            <label><input type="checkbox" wire:model.live="editor.has_note"> Include Notes</label>
        </div>
        @if ($editor['has_note'] ?? false)<label class="tb-field">Notes Label<input class="tb-input" wire:model.live="editor.note_label"></label>@endif
        <details><summary>Conditional Visibility</summary>
            <label class="tb-field">Parent Question<select class="tb-select" wire:model.live="editor.parent_question_id"><option value="">Always visible</option>@foreach ($this->getInlineParents() as $parent)<option value="{{ $parent->id }}">{{ $parent->prompt }}</option>@endforeach</select></label>
            @if ($editor['parent_question_id'] ?? null)<label class="tb-field">Show When<select class="tb-select" wire:model.live="editor.trigger_answer"><option value="">Select</option><option value="yes">Yes</option><option value="no">No</option></select></label>@endif
        </details>
        <div class="tb-actions"><button type="button" class="tb-button" wire:click="cancelInlineQuestion">Cancel</button><button type="button" class="tb-button tb-button--primary" wire:click="saveInlineQuestion" wire:loading.attr="disabled">Save Question</button></div>
    </div>
</div>
