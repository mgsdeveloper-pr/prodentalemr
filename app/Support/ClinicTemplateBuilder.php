<?php

namespace App\Support;

use App\Models\VerificationFormQuestion as Question;
use App\Models\VerificationTemplateVersion as Version;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClinicTemplateBuilder
{
    public function editable(int $id): Version
    {
        $version = Version::query()->where('scope', 'clinic')
            ->where('clinic_id', ClinicPanelScope::selectedClinicId())->lockForUpdate()->findOrFail($id);
        abort_unless(auth()->user()?->canManageClinicTemplateSections($version->clinic), 403);
        if (! $version->canEditDirectly()) {
            throw ValidationException::withMessages(['editor' => 'This version is protected. Create a named draft to change it.']);
        }
        return $version;
    }

    public function save(int $versionId, ?int $id, array $input): Question
    {
        return DB::transaction(function () use ($versionId, $id, $input) {
            $version = $this->editable($versionId);
            $question = $id ? $version->questions()->findOrFail($id) : new Question;
            $data = Validator::make($input, [
                'prompt' => 'required|string|max:1000', 'section_key' => 'required|string',
                'input_type' => ['required', Rule::in(array_keys(Question::INPUT_TYPE_OPTIONS))],
                'mapping' => 'required|string', 'confirm_disconnect' => 'boolean',
                'select_options' => 'nullable|string|max:10000', 'help_text' => 'nullable|string|max:2000',
                'placeholder' => 'nullable|string|max:255', 'is_active' => 'required|boolean',
                'is_required_for_audit' => 'required|boolean', 'has_note' => 'boolean',
                'note_label' => 'nullable|string|max:255',
                'parent_question_id' => 'nullable|integer', 'trigger_answer' => 'nullable|in:yes,no',
                'answer_layout' => ['nullable', Rule::in(array_keys(Question::ANSWER_LAYOUT_OPTIONS))],
                'frequency_response_mode' => 'nullable|in:current,advanced',
                'frequency_response_fields' => 'array',
                'frequency_response_fields.*' => [Rule::in(array_keys(Question::FREQUENCY_ADVANCED_OPTIONAL_FIELDS))],
                'procedure_codes' => 'nullable|string|max:1000', 'code_system' => 'nullable|in:CDT,CPT',
            ])->validate();
            $section = $version->sections()->where('section_key', $data['section_key'])->first();
            if ((! $section && ! in_array($data['section_key'], Question::TEMPLATE_3_LIVE_SECTION_KEYS, true))
                || ($section && (! $section->is_active || ($section->parent_section_key && ! $version->sections()->where('section_key', $section->parent_section_key)->where('is_active', true)->exists())))) {
                throw ValidationException::withMessages(['editor.section_key' => 'Choose an active section in this draft.']);
            }
            $mapping = $data['mapping'];
            $fields = VerificationTemplateImport::mappingOptions(true);
            if ($mapping === 'keep' && $question->exists && $question->is_builtin) {
                $definition = $question->only(['field_key', 'input_type', 'secondary_field_key', 'secondary_input_type', 'select_options']);
            } elseif (isset($fields[$mapping])) {
                $definition = $fields[$mapping];
                $definition['field_key'] = $mapping;
            } elseif ($mapping === 'standalone') {
                if ($question->is_builtin && ! ($data['confirm_disconnect'] ?? false)) {
                    throw ValidationException::withMessages(['editor.confirm_disconnect' => 'Confirm disconnecting this question from its existing data field.']);
                }
                $definition = null;
            } else {
                throw ValidationException::withMessages(['editor.mapping' => 'Choose an existing data field or a standalone question.']);
            }
            if ($definition) {
                if ($data['input_type'] !== $definition['input_type']) {
                    throw ValidationException::withMessages(['editor.input_type' => 'Answer type does not match the data field. Remap or explicitly choose standalone.']);
                }
                if ($mapping === 'keep' && in_array($data['input_type'], ['select', 'multi_select'], true)
                    && trim((string) ($data['select_options'] ?? '')) !== trim((string) ($definition['select_options'] ?? ''))) {
                    throw ValidationException::withMessages(['editor.select_options' => 'The connected field requires its existing choices. Choose standalone to use different choices.']);
                }
                $keys = array_filter([$definition['field_key'], $definition['secondary_field_key'] ?? null]);
                if ($version->questions()->when($id, fn ($q) => $q->whereKeyNot($id))->where('is_builtin', true)
                    ->where(fn ($q) => $q->whereIn('field_key', $keys)->orWhereIn('secondary_field_key', $keys))->exists()) {
                    throw ValidationException::withMessages(['editor.mapping' => 'This data field is already connected to another question.']);
                }
                $data = array_replace($data, Arr::only($definition, ['field_key', 'secondary_field_key', 'secondary_input_type', 'select_options']));
                $data['secondary_field_key'] = $definition['secondary_field_key'] ?? null;
                $data['secondary_input_type'] = $definition['secondary_input_type'] ?? null;
                $data['is_builtin'] = true;
            } else {
                $data['field_key'] = $question->exists && ! $question->is_builtin ? $question->field_key : 'clinic_'.\Illuminate\Support\Str::uuid();
                $data['secondary_field_key'] = $data['secondary_input_type'] = null;
                $data['is_builtin'] = false;
            }
            if (in_array($data['input_type'], ['select', 'multi_select'], true) && blank($data['select_options'] ?? null)) {
                throw ValidationException::withMessages(['editor.select_options' => 'Enter at least one answer choice.']);
            }
            $parentId = $data['parent_question_id'] ?? null;
            if ($parentId) {
                $parent = $version->questions()->where('is_active', true)->find($parentId);
                if (! $parent || $parent->id === $id || $parent->input_type !== 'yes_no' || blank($data['trigger_answer'] ?? null)) {
                    throw ValidationException::withMessages(['editor.parent_question_id' => 'Choose an active Yes / No parent and a trigger.']);
                }
                $visited = [$id];
                while ($parent) {
                    if (in_array($parent->id, $visited, true)) throw ValidationException::withMessages(['editor.parent_question_id' => 'This condition creates a circular dependency.']);
                    $visited[] = $parent->id;
                    $parent = $parent->parent_question_id ? $version->questions()->find($parent->parent_question_id) : null;
                }
            }
            $data['question_kind'] = $parentId ? Question::QUESTION_KIND_CONDITIONAL : Question::QUESTION_KIND_NORMAL;
            $data['parent_question_id'] = $parentId ?: null;
            $data['trigger_answer'] = $parentId ? $data['trigger_answer'] : null;
            if ($data['input_type'] === 'frequency_row') {
                if (blank($data['answer_layout'] ?? null)) throw ValidationException::withMessages(['editor.answer_layout' => 'Choose the answer layout.']);
                $data['frequency_response_mode'] = $data['frequency_response_mode'] ?? 'current';
            } else {
                $data['answer_layout'] = $data['frequency_response_mode'] = $data['frequency_response_fields'] = null;
            }
            if (array_key_exists('procedure_codes', $data)) {
                $inspection = VerificationProcedureTags::inspect(['question' => $data['prompt'],
                    'procedure_codes' => $data['procedure_codes'] ?? '', 'code_system' => $data['code_system'] ?? 'CDT']);
                if ($inspection['errors']) throw ValidationException::withMessages(['editor.procedure_codes' => $inspection['errors']]);
                $data['procedure_tags'] = $inspection['tags'];
                $data['code'] = $data['input_type'] === 'frequency_row' && $inspection['tags']
                    ? implode('/', array_column($inspection['tags'], 'code')) : null;
            }
            unset($data['procedure_codes'], $data['code_system']);
            unset($data['mapping'], $data['confirm_disconnect']);
            if ($question->exists && ($question->input_type !== $data['input_type'] || $question->field_key !== $data['field_key'])) {
                $question->semantic_key = 'question:'.\Illuminate\Support\Str::uuid();
            }
            $question->fill($data);
            if (! $question->exists) {
                $question->fill(['clinic_id' => $version->clinic_id, 'organization_id' => $version->organization_id,
                    'template_version_id' => $version->id, 'template_key' => $version->template_key,
                    'form_type' => $version->form_type, 'sort_order' => 10 + (int) $version->questions()->where('section_key', $data['section_key'])->max('sort_order')]);
            }
            $question->save();
            return $question;
        });
    }

    public function remove(int $versionId, array $ids, array $sectionKeys = []): void
    {
        DB::transaction(function () use ($versionId, $ids, $sectionKeys) {
            $version = $this->editable($versionId);
            if ($sectionKeys && $version->questions()->whereIn('section_key', $sectionKeys)->whereNotIn('id', $ids)->exists()) {
                throw ValidationException::withMessages(['removal' => 'This section changed after review. Cancel and review the removal again.']);
            }
            if ($version->questions()->whereNotIn('id', $ids)->whereIn('parent_question_id', $ids)->exists()) {
                throw ValidationException::withMessages(['removal' => 'Other questions depend on this selection. Remove or change their conditions first.']);
            }
            $version->questions()->whereIn('id', $ids)->delete();
            $version->sections()->whereIn('section_key', $sectionKeys)->delete();
        });
    }
}
