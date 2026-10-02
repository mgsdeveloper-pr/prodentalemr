<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;
use App\Models\VerificationFormQuestion as Question;
use App\Models\VerificationTemplateVersion as Version;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as ExcelReader;
use OpenSpout\Writer\XLSX\Writer;
use ZipArchive;

class VerificationTemplateImport
{
    public const HEADERS = ['section_key', 'question_key', 'question', 'answer_type', 'required_for_audit', 'choices', 'form_type'];

    public const V2_HEADERS = [...self::HEADERS, 'format_version', 'section_name', 'subsection_key', 'subsection_name', 'section_order', 'subsection_order', 'question_order', 'code_system', 'procedure_codes', 'question_purpose', 'code_relationship'];

    public const V3_HEADERS = [...self::V2_HEADERS, 'secondary_field_key', 'secondary_input_type', 'answer_layout', 'frequency_response_mode', 'frequency_response_fields', 'response_category'];

    public const MAX_ROWS = 500;

    public static function types(): array
    {
        return array_diff_key(Question::INPUT_TYPE_OPTIONS, ['frequency_row' => true]);
    }

    public static function mappingOptions(bool $compound = false): array
    {
        $fields = collect(VerificationTemplateThreeDefaults::questions())
            ->filter(fn ($field) => filled($field['field_key'] ?? null) && ($compound || empty($field['secondary_field_key'])))
            ->keyBy('field_key')->all();
        foreach (['context_clinic_name' => 'Clinic name', 'vf_appointment_date' => 'Appointment date'] as $key => $label) {
            $fields[$key] = self::mappedField(['question_key' => $key, 'section_key' => 'template_3_patient_subscriber']);
        }
        return $fields;
    }

    public function reviewMappings(array $rows, array $mappings, string $formType): array
    {
        Validator::make(['formType' => $formType], ['formType' => ['required', Rule::in(['short_form', 'full_form'])]])->validate();
        $fields = self::mappingOptions(true);
        $errors = [];
        foreach ($rows as $index => &$row) {
            $number = $row['row'] ?? $index + 2;
            $destination = $mappings[$index] ?? '';
            if (! in_array($row['form_type'], [$formType, 'both'], true)) {
                $errors[] = "Row {$number}: belongs to the other form. Correct the file or choose the matching form.";
            }
            $row['form_type'] = $formType;
            if ($destination === 'custom') {
                if (self::mappedField($row) || str_starts_with($row['question_key'], 'vf_') || str_starts_with($row['question_key'], 'context_')) {
                    $errors[] = "Row {$number}: a recognized system field cannot become a separate custom answer.";
                }
            } elseif (isset($fields[$destination])) {
                $field = $fields[$destination];
                $v3 = ($row['format_version'] ?? '') === '3';
                if (! $v3 && filled($row['subsection_key'] ?? null) && $row['section_key'] !== $field['section_key']) {
                    $errors[] = "Row {$number}: mapped answer belongs to another section; correct the hierarchy before mapping.";
                }
                if (! $v3) $row['section_key'] = $field['section_key'];
                if ($v3 && $row['answer_type'] !== $field['input_type']) {
                    $errors[] = "Row {$number}: answer type must match the selected existing field.";
                }
                $row['question_key'] = $destination;
                $row['answer_type'] = $field['input_type'];
                $options = $field['select_options'] ?? '';
                $row['choices'] = is_array($options) ? implode('|', $options) : implode('|', preg_split('/\s*[,\r\n]+\s*/', trim($options), -1, PREG_SPLIT_NO_EMPTY));
            } else {
                $errors[] = "Row {$number}: confirm an existing field or Custom question.";
            }
        }
        unset($row);
        $result = $this->validateRows($rows, $errors);
        $labels = [];
        foreach ($rows as $index => $row) {
            $label = $row['section_key'].'|'.($row['subsection_key'] ?? '').'|'.mb_strtolower(preg_replace('/\s+/u', ' ', trim($row['question'])));
            if (isset($labels[$label])) {
                $result['errors'][] = 'Row '.($row['row'] ?? $index + 2).': duplicate question label. Review both questions.';
            }
            $labels[$label] = true;
        }
        return $result;
    }

    public static function mappedField(array $row): ?array
    {
        $aliases = [
            'short_clinic_name' => 'context_clinic_name',
            'short_appointment_date' => 'vf_appointment_date',
            'short_patient_name' => 'vf_patient_full_name',
            'short_patient_dob' => 'vf_patient_dob',
            'short_subscriber_name' => 'vf_subscriber_name',
            'short_subscriber_dob' => 'vf_subscriber_dob',
            'short_provider_in_network' => 'vf_network_status',
            'short_insurance_name' => 'vf_insurance_provider_name',
            'short_insurance_phone' => 'vf_insurance_company_phone_number',
            'short_effective_date' => 'vf_effective_date',
            'short_employer_group_name' => 'vf_group_name',
            'short_group_number' => 'vf_group_number',
            'short_annual_maximum' => 'vf_annual_maximum',
            'short_remaining_maximum' => 'vf_annual_maximum_remaining',
            'short_annual_individual_deductible' => 'vf_individual_deductible',
            'short_individual_deductible_met' => 'vf_individual_deductible_met_display',
            'short_waiting_period' => 'vf_waiting_periods',
            'short_exams_history' => 'vf_history_exams',
            'short_prophy_history' => 'vf_history_prophylaxis',
            'short_bitewings_history' => 'vf_history_bitewings',
            'short_fmx_pano_history' => 'vf_history_full_mouth_xray',
            'short_major_basic_history' => 'vf_history_basic_or_major',
            'short_additional_comments' => 'vf_verification_notes',
            'short_verification_date' => 'vf_verification_date',
            'short_verified_by' => 'vf_verified_by',
            'short_insurance_representative_name' => 'vf_insurance_representative_name',
        ];
        $key = $aliases[$row['question_key'] ?? ''] ?? ($row['question_key'] ?? '');
        $fields = collect(VerificationTemplateThreeDefaults::questions())
            ->filter(fn ($field) => filled($field['field_key'] ?? null)
                && (($row['format_version'] ?? '') === '3' || empty($field['secondary_field_key'])))->keyBy('field_key')->all();
        foreach ([
            ['context_clinic_name', 'Clinic name', 'text', 'template_3_patient_subscriber'],
            ['vf_appointment_date', 'Appointment date', 'date', 'template_3_patient_subscriber'],
        ] as [$field, $label, $type, $section]) {
            $fields[$field] = ['field_key' => $field, 'prompt' => $label, 'input_type' => $type, 'section_key' => $section];
        }

        return isset($fields[$key]) && (($row['format_version'] ?? '') === '3' || $fields[$key]['section_key'] === ($row['section_key'] ?? ''))
            ? $fields[$key] : null;
    }

    public function authorize(User $user, ?Clinic $clinic): void
    {
        $allowed = $clinic
            ? $clinic->status && ($user->shouldBypassClinicScope()
                || ((int) $user->clinic_id === (int) $clinic->id && (int) $user->organization_id === (int) $clinic->organization_id)
                || $user->canAccessVerificationClinic($clinic->id))
                && $user->canManageClinicTemplateSections($clinic)
            : $user->canManageVerificationTemplateSections();
        abort_unless($user->status && $allowed, 403);
    }

    public function read(string $path, string $name): array
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (! in_array($extension, ['xlsx', 'csv'], true) || filesize($path) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['upload' => 'Upload an XLSX or CSV file up to 5 MB.']);
        }
        if ($extension === 'xlsx') {
            $this->checkArchive($path);
        }
        $reader = $extension === 'xlsx' ? new ExcelReader : new CsvReader;
        $rows = [];
        $errors = [];
        $headers = self::HEADERS;
        try {
            $reader->open($path);
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $number => $row) {
                    if ($number > self::MAX_ROWS + 1) {
                        throw ValidationException::withMessages(['upload' => 'Limit the file to 500 questions.']);
                    }
                    $values = [];
                    foreach ($row->getCells() as $cell) {
                        if ($cell instanceof FormulaCell || ! is_scalar($cell->getValue()) && $cell->getValue() !== null) {
                            throw ValidationException::withMessages(['upload' => "Row {$number}: formulas and date-formatted cells are not supported. Use plain text."]);
                        }
                        $values[] = trim((string) $cell->getValue());
                    }
                    if ($number === 1) {
                        $values[0] = ltrim($values[0] ?? '', "\xEF\xBB\xBF");
                        if (! in_array($values, [self::HEADERS, self::V2_HEADERS, self::V3_HEADERS], true)) {
                            throw ValidationException::withMessages(['upload' => 'The column headings must match the downloaded sample, in the same order.']);
                        }
                        $headers = $values;

                        continue;
                    }
                    if (count(array_filter($values, fn ($v) => $v !== '')) === 0) {
                        continue;
                    }
                    if (count($values) > count($headers)) {
                        $errors[] = "Row {$number}: unexpected extra columns.";

                        continue;
                    }
                    $rows[] = array_combine($headers, array_pad($values, count($headers), '')) + ['row' => $number];
                }
                break;
            }
        } finally {
            $reader->close();
        }

        return $this->validateRows($rows, $errors);
    }

    public function validateRows(array $rows, array $errors = []): array
    {
        if (! count($rows) || count($rows) > self::MAX_ROWS) {
            $errors[] = 'Include between 1 and 500 questions.';
        }
        $keys = [];
        $mappedKeys = [];
        $definitions = [];
        foreach ($rows as $index => $row) {
            $number = $row['row'] ?? $index + 2;
            $v3 = ($row['format_version'] ?? '') === '3';
            if ($v3 && ! in_array($row['section_key'] ?? '', Question::TEMPLATE_3_LIVE_SECTION_KEYS, true) && ! str_starts_with($row['section_key'] ?? '', 'custom_')) {
                $errors[] = "Row {$number}: use a known section key or a custom_ identifier.";
            }
            $validator = Validator::make($row, [
                'section_key' => $v3 ? ['required', 'regex:/^(?:template_3_[a-z0-9_]+|custom_[a-z0-9_]{1,170})$/'] : ['required', Rule::in(Question::TEMPLATE_3_LIVE_SECTION_KEYS)],
                'question_key' => ['required', 'regex:/^[a-z][a-z0-9_]{0,79}$/'],
                'question' => ['required', 'string', 'max:255'],
                'answer_type' => ['required', Rule::in(array_keys($v3 ? Question::INPUT_TYPE_OPTIONS : self::types()))],
                'required_for_audit' => ['required', Rule::in(['yes', 'no'])],
                'choices' => ['nullable', 'string', 'max:4000'],
                'form_type' => ['required', Rule::in(array_keys(Question::FORM_TYPE_OPTIONS))],
            ]);
            foreach ($validator->errors()->all() as $error) {
                $errors[] = "Row {$number}: {$error}";
            }
            if (array_key_exists('format_version', $row)) {
                $extra = Validator::make($row, [
                    'format_version' => ['required', Rule::in(['2', '3'])],
                    'section_name' => ['required', 'string', 'max:255'],
                    'subsection_key' => ['nullable', 'regex:/^custom_[a-z0-9_]{1,170}$/'],
                    'subsection_name' => ['required_with:subsection_key', 'nullable', 'string', 'max:255'],
                    'section_order' => ['required', 'integer', 'min:0', 'max:10000'],
                    'subsection_order' => ['required_with:subsection_key', 'nullable', 'integer', 'min:0', 'max:10000'],
                    'question_order' => ['required', 'integer', 'min:0', 'max:10000'],
                    'code_system' => ['nullable', Rule::in(['CDT', 'CPT'])],
                    'procedure_codes' => ['nullable', 'string', 'max:500'],
                    'question_purpose' => ['required', Rule::in(['frequency', 'coverage', 'history', 'limitation', 'downgrade', 'other'])],
                    'code_relationship' => ['nullable', Rule::in(['individual', 'grouped', 'shared_frequency'])],
                ]);
                foreach ($extra->errors()->all() as $error) {
                    $errors[] = "Row {$number}: {$error}";
                }
                if (filled($row['subsection_name'] ?? null) && blank($row['subsection_key'] ?? null)) {
                    $errors[] = "Row {$number}: subsection name needs a subsection identifier.";
                }
                // Repeated definitions must agree throughout the upload.
                foreach ([[$row['section_key'], null, $row['section_name'], $row['section_order']], [$row['subsection_key'] ?? '', $row['section_key'], $row['subsection_name'] ?? '', $row['subsection_order'] ?? '']] as $definition) {
                    if ($definition[0] === '') continue;
                    if (isset($definitions[$definition[0]]) && $definitions[$definition[0]] !== $definition) {
                        $errors[] = "Row {$number}: conflicting section name, parent or order.";
                    }
                    $definitions[$definition[0]] = $definition;
                }
                $codeReview = VerificationProcedureTags::inspect($row);
                foreach ($codeReview['errors'] as $error) $errors[] = "Row {$number}: {$error}";
                if ($codeReview['tags'] && blank($row['code_relationship'] ?? null)) {
                    $errors[] = "Row {$number}: confirm the relationship between procedure codes.";
                }
                if (count($codeReview['tags']) > 1 && ($row['code_relationship'] ?? '') === 'individual') {
                    $errors[] = "Row {$number}: multiple codes require grouped or shared_frequency.";
                }
            }
            if ($v3) {
                foreach (array_diff(self::V3_HEADERS, array_keys($row)) as $missing) $errors[] = "Row {$number}: missing V3 column {$missing}.";
                $this->validateCompoundAnswer($row, $number, $errors);
            }
            $key = $row['question_key'] ?? '';
            if (isset($keys[$key])) {
                $errors[] = "Row {$number}: duplicate question key {$key}.";
            }
            $keys[$key] = true;
            $mapping = self::mappedField($row);
            if ($mapping) {
                if (isset($mappedKeys[$mapping['field_key']])) {
                    $errors[] = "Row {$number}: duplicate mapping to {$mapping['field_key']}.";
                }
                $mappedKeys[$mapping['field_key']] = true;
                if ($v3 && $row['answer_type'] !== $mapping['input_type']) $errors[] = "Row {$number}: mapped answer type is incompatible.";
            } elseif (str_starts_with($key, 'vf_') || str_starts_with($key, 'context_')) {
                $errors[] = "Row {$number}: unknown system field or incorrect section for {$key}.";
            }
            if ($v3 && filled($secondary = $row['secondary_field_key'] ?? '')) {
                if (isset($mappedKeys[$secondary])) $errors[] = "Row {$number}: duplicate mapping to {$secondary}.";
                $mappedKeys[$secondary] = true;
            }
            foreach (self::V3_HEADERS as $header) {
                if (preg_match('/^[=+@]/', $row[$header] ?? '')) {
                    $errors[] = "Row {$number}: {$header} must be plain text, not a formula.";
                }
            }
            $choices = ($row['choices'] ?? '') === '' ? [] : array_map('trim', explode('|', $row['choices']));
            $select = in_array($row['answer_type'] ?? '', ['select', 'multi_select'], true);
            if ($select && ! $mapping && (! count($choices) || in_array('', $choices, true) || count(array_unique($choices)) !== count($choices))) {
                $errors[] = "Row {$number}: dropdown/multi-response choices must be non-empty and unique, separated by |.";
            }
            if (! $select && count($choices)) {
                $errors[] = "Row {$number}: choices apply only to select or multi_select answers.";
            }
        }

        foreach ($definitions as $key => $definition) {
            if ($definition[1] !== null && isset($definitions[$definition[1]]) && $definitions[$definition[1]][1] !== null) {
                $errors[] = "Section {$key}: only one subsection level is supported.";
            }
        }
        if (count(array_unique(array_map(fn ($row) => $row['format_version'] ?? '1', $rows))) > 1) $errors[] = 'Do not mix import format versions.';

        return ['rows' => $rows, 'errors' => $errors];
    }

    protected function validateCompoundAnswer(array $row, int $number, array &$errors): void
    {
        $frequency = ($row['answer_type'] ?? '') === 'frequency_row';
        $rules = [
            'secondary_field_key' => ['nullable', 'string', 'max:80'],
            'secondary_input_type' => ['nullable', Rule::in(['percent', 'currency', 'text', 'date', 'yes_no'])],
            'answer_layout' => [$frequency ? 'required' : 'nullable', Rule::in(array_keys(Question::ANSWER_LAYOUT_OPTIONS))],
            'frequency_response_mode' => [$frequency ? 'required' : 'nullable', Rule::in(['current', 'advanced'])],
            'response_category' => [$frequency ? 'required' : 'nullable', Rule::in(['General', 'Diagnostic & Preventative', 'Basic', 'Major', 'Orthodontics'])],
        ];
        foreach (Validator::make($row, $rules)->errors()->all() as $error) $errors[] = "Row {$number}: {$error}";
        $fields = array_filter(explode('|', $row['frequency_response_fields'] ?? ''));
        if (array_diff($fields, array_keys(Question::frequencyResponseFieldOptions($row['frequency_response_mode'] ?? null)))) $errors[] = "Row {$number}: unsupported frequency detail field.";
        if (! $frequency && (filled($row['answer_layout'] ?? '') || filled($row['frequency_response_mode'] ?? '') || $fields || filled($row['response_category'] ?? ''))) $errors[] = "Row {$number}: frequency settings need a frequency_row answer.";
        $mapping = self::mappedField($row);
        $secondary = $row['secondary_field_key'] ?? '';
        if (filled($secondary)) {
            if (! $mapping || ($mapping['secondary_field_key'] ?? '') !== $secondary || ($mapping['secondary_input_type'] ?? '') !== ($row['secondary_input_type'] ?? '')) {
                $errors[] = "Row {$number}: paired answers must use the existing field pair and its answer type.";
            }
        } elseif (filled($row['secondary_input_type'] ?? '') || filled($mapping['secondary_field_key'] ?? '')) {
            $errors[] = "Row {$number}: include both fields of the existing answer pair.";
        }
    }

    public function createDraft(User $user, ?Clinic $clinic, string $name, array $rows, ?string $token = null): Version
    {
        $this->authorize($user, $clinic);
        Validator::make(['name' => trim($name)], ['name' => ['required', 'string', 'max:255']])->validate();
        $review = $this->validateRows($rows);
        if ($review['errors']) {
            throw ValidationException::withMessages(['upload' => $review['errors']]);
        }

        $token ??= (string) \Illuminate\Support\Str::uuid();
        Validator::make(['token' => $token], ['token' => 'required|uuid'])->validate();
        $hash = hash('sha256', json_encode([trim($name), $rows], JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($user, $clinic, $name, $rows, $token, $hash): Version {
            // Serialize retries before creating either the receipt or the draft.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $receipt = \App\Models\VerificationTemplateImportReceipt::where('token', $token)->first();
            if ($receipt) {
                abort_unless((int) $receipt->user_id === (int) $user->id && $receipt->clinic_id === $clinic?->id, 403);
                if (! hash_equals($receipt->payload_hash, $hash)) {
                    throw ValidationException::withMessages(['upload' => 'This import was already saved with different content. Start a new upload.']);
                }
                $previous = Version::find($receipt->template_version_id);
                if (! $previous) {
                    throw ValidationException::withMessages(['upload' => 'The previously imported draft was deleted. Start a new upload to import again.']);
                }
                return $previous;
            }
            $formTypes = array_unique(array_column($rows, 'form_type'));
            $draft = app(VerificationTemplateVersionService::class)->createDraftFromSource(null, [
                'scope' => $clinic ? Version::SCOPE_CLINIC : Version::SCOPE_MASTER,
                'organization_id' => $clinic?->organization_id,
                'clinic_id' => $clinic?->id,
                'name' => trim($name),
                'starting_point' => 'fresh',
                'form_type' => count($formTypes) === 1 ? reset($formTypes) : Version::FORM_TYPE_BOTH,
            ]);
            $hierarchical = ($rows[0]['format_version'] ?? '') === '3';
            $draft->update(['uses_section_layout' => $hierarchical, 'created_by' => $user->id, 'notes' => 'Imported from a reviewed structured template. Published templates and existing answers were not changed.']);
            if ($hierarchical) $draft->sections()->delete();
            foreach ($rows as $index => $row) {
                $mapping = self::mappedField($row);
                $v2 = in_array($row['format_version'] ?? '', ['2', '3'], true);
                if ($v2) {
                    foreach ([[$row['section_key'], null, $row['section_name'], $row['section_order']], [$row['subsection_key'] ?? '', $row['section_key'], $row['subsection_name'] ?? '', $row['subsection_order'] ?? 0]] as [$key, $parent, $label, $order]) {
                        if ($key === '') continue;
                        $draft->sections()->updateOrCreate(['section_key' => $key], [
                            'template_key' => $draft->template_key, 'organization_id' => $clinic?->organization_id,
                            'clinic_id' => $clinic?->id, 'parent_section_key' => $parent,
                            'label' => $label, 'sort_order' => (int) $order, 'is_active' => true,
                            'is_builtin' => in_array($key, Question::TEMPLATE_3_LIVE_SECTION_KEYS, true),
                        ]);
                    }
                }
                $draft->questions()->create([
                    'organization_id' => $clinic?->organization_id,
                    'clinic_id' => $clinic?->id,
                    'template_key' => $draft->template_key,
                    'section_key' => filled($row['subsection_key'] ?? null) ? $row['subsection_key'] : $row['section_key'],
                    'field_key' => $mapping['field_key'] ?? $row['question_key'],
                    'prompt' => $row['question'],
                    'input_type' => $mapping['input_type'] ?? $row['answer_type'],
                    'is_required_for_audit' => $row['required_for_audit'] === 'yes',
                    'select_options' => $mapping ? ($mapping['select_options'] ?? null) : implode("\n", array_map('trim', explode('|', $row['choices']))),
                    'form_type' => $row['form_type'],
                    'question_kind' => Question::QUESTION_KIND_NORMAL,
                    'sort_order' => $v2 ? (int) $row['question_order'] : ($index + 1) * 10,
                    'procedure_tags' => $v2 ? VerificationProcedureTags::inspect($row)['tags'] : null,
                    'secondary_field_key' => $hierarchical ? ($row['secondary_field_key'] ?: null) : null,
                    'secondary_input_type' => $hierarchical ? ($row['secondary_input_type'] ?: null) : null,
                    'answer_layout' => $hierarchical ? ($row['answer_layout'] ?: null) : null,
                    'response_category' => $hierarchical ? ($row['response_category'] ?: null) : null,
                    'frequency_response_mode' => $hierarchical ? ($row['frequency_response_mode'] ?: null) : null,
                    'frequency_response_fields' => $hierarchical && $row['answer_type'] === 'frequency_row' ? array_values(array_filter(explode('|', $row['frequency_response_fields']))) : null,
                    'is_builtin' => $mapping !== null,
                    'is_active' => true,
                ]);
            }

            \App\Models\VerificationTemplateImportReceipt::create([
                'token' => $token, 'user_id' => $user->id, 'clinic_id' => $clinic?->id,
                'template_version_id' => $draft->id, 'payload_hash' => $hash,
                'name' => trim($name), 'form_type' => $draft->form_type, 'question_count' => count($rows),
            ]);
            return $draft;
        });
    }

    public function reviewSection(Version $draft, string $sectionKey, string $formType, array $rows): array
    {
        $review = $this->validateRows($rows);
        $section = $draft->sections()->where('section_key', $sectionKey)->first();
        if (! $draft->canEditDirectly() || ! $section || ! $section->is_active) {
            $review['errors'][] = 'Select an active section in an unused draft.';
        }
        if (! in_array($formType, ['full_form', 'short_form'], true) || ! in_array($draft->form_type, ['both', $formType], true)) {
            $review['errors'][] = 'The form type does not match this draft.';
        }
        $existing = $draft->questions()->get();
        $fields = $existing->pluck('field_key')->merge($existing->pluck('secondary_field_key'))->filter()->all();
        $prompts = $existing->map(fn ($q) => mb_strtolower(trim($q->prompt)))->all();
        foreach ($rows as $index => $row) {
            $number = $row['row'] ?? $index + 2;
            $target = filled($row['subsection_key'] ?? null) ? $row['subsection_key'] : $row['section_key'];
            if ($target !== $sectionKey || ! in_array($row['form_type'], [$formType, 'both'], true)) {
                $review['errors'][] = "Row {$number}: section or form type differs from the selected destination.";
            }
            $mapping = self::mappedField($row);
            $key = $mapping['field_key'] ?? $row['question_key'];
            if (in_array($key, $fields, true) || (filled($row['secondary_field_key'] ?? null) && in_array($row['secondary_field_key'], $fields, true))) {
                $review['errors'][] = "Row {$number}: this answer field already exists in the draft.";
            }
            $prompt = mb_strtolower(trim($row['question']));
            if (in_array($prompt, $prompts, true)) {
                $review['errors'][] = "Row {$number}: this question wording already exists. Edit the existing question instead.";
            }
            $prompts[] = $prompt;
        }
        return $review;
    }

    public function appendToSection(User $user, Version $draft, string $sectionKey, string $formType, array $rows, string $token): void
    {
        abort_unless($draft->scope === Version::SCOPE_CLINIC && $draft->clinic, 403);
        $this->authorize($user, $draft->clinic);
        Validator::make(['token' => $token], ['token' => 'required|uuid'])->validate();
        $hash = hash('sha256', json_encode([$draft->id, $sectionKey, $formType, $rows], JSON_THROW_ON_ERROR));
        DB::transaction(function () use ($user, $draft, $sectionKey, $formType, $rows, $token, $hash): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $draft = Version::query()->lockForUpdate()->findOrFail($draft->id);
            $receipt = \App\Models\VerificationTemplateImportReceipt::where('token', $token)->first();
            if ($receipt) {
                abort_unless((int) $receipt->user_id === (int) $user->id && (int) $receipt->template_version_id === (int) $draft->id, 403);
                if (! hash_equals($receipt->payload_hash, $hash)) {
                    throw ValidationException::withMessages(['upload' => 'This upload was already saved with different content.']);
                }
                return;
            }
            $review = $this->reviewSection($draft, $sectionKey, $formType, $rows);
            if ($review['errors']) throw ValidationException::withMessages(['upload' => $review['errors']]);
            $order = (int) $draft->questions()->where('section_key', $sectionKey)->max('sort_order');
            foreach ($rows as $row) {
                $mapping = self::mappedField($row);
                $draft->questions()->create([
                    'organization_id' => $draft->organization_id, 'clinic_id' => $draft->clinic_id,
                    'template_key' => $draft->template_key, 'section_key' => $sectionKey,
                    'field_key' => $mapping['field_key'] ?? $row['question_key'], 'prompt' => $row['question'],
                    'input_type' => $mapping['input_type'] ?? $row['answer_type'],
                    'is_required_for_audit' => $row['required_for_audit'] === 'yes',
                    'select_options' => $mapping ? ($mapping['select_options'] ?? null) : implode("\n", array_map('trim', explode('|', $row['choices']))),
                    'form_type' => $formType, 'question_kind' => Question::QUESTION_KIND_NORMAL,
                    'sort_order' => $order += 10, 'procedure_tags' => VerificationProcedureTags::inspect($row)['tags'],
                    'secondary_field_key' => ($row['secondary_field_key'] ?? '') ?: null,
                    'secondary_input_type' => ($row['secondary_input_type'] ?? '') ?: null,
                    'answer_layout' => ($row['answer_layout'] ?? '') ?: null,
                    'response_category' => ($row['response_category'] ?? '') ?: null,
                    'frequency_response_mode' => ($row['frequency_response_mode'] ?? '') ?: null,
                    'frequency_response_fields' => $row['answer_type'] === 'frequency_row' ? array_values(array_filter(explode('|', $row['frequency_response_fields'] ?? ''))) : null,
                    'is_builtin' => $mapping !== null, 'is_active' => true,
                ]);
            }
            \App\Models\VerificationTemplateImportReceipt::create([
                'token' => $token, 'user_id' => $user->id, 'clinic_id' => $draft->clinic_id,
                'template_version_id' => $draft->id, 'payload_hash' => $hash,
                'name' => $draft->name, 'form_type' => $formType, 'question_count' => count($rows),
            ]);
        });
    }

    public function sampleRows(): array
    {
        return [
            ['template_3_plan_provisions', 'example_waiting_period', 'Does a waiting period apply?', 'yes_no', 'yes', '', 'both'],
            ['template_3_maximums_deductibles', 'example_annual_maximum', 'Annual maximum', 'currency', 'no', '', 'full_form'],
            ['template_3_plan_provisions', 'example_plan_year', 'Plan year basis', 'select', 'no', 'Calendar year|Contract year', 'both'],
        ];
    }

    public function writeWorkbook(string $path, array $rows, array $headers = self::HEADERS): void
    {
        $writer = new Writer;
        $writer->openToFile($path);
        try {
            $writer->getCurrentSheet()->setName('Questions');
            $writer->addRow(Row::fromValues($headers));
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }
            $writer->addNewSheetAndMakeItCurrent()->setName('Allowed Values');
            $writer->addRow(Row::fromValues(['section_key', 'Section']));
            foreach (Question::templateThreeLiveSectionOptions() as $key => $label) {
                $writer->addRow(Row::fromValues([$key, $label]));
            }
            $writer->addRow(Row::fromValues(['answer_type', implode(', ', array_keys(self::types()))]));
            $writer->addRow(Row::fromValues(['required_for_audit', 'yes, no']));
            $writer->addRow(Row::fromValues(['form_type', 'both, full_form, short_form']));
            $writer->addRow(Row::fromValues(['choices', 'Separate choices with |. Only for select and multi_select.']));
            if ($headers === self::V3_HEADERS) {
                $writer->addRow(Row::fromValues(['V3 answer_type', implode(', ', array_keys(Question::INPUT_TYPE_OPTIONS))]));
                $writer->addRow(Row::fromValues(['answer_layout', implode(', ', array_keys(Question::ANSWER_LAYOUT_OPTIONS))]));
                $writer->addRow(Row::fromValues(['frequency_response_mode', 'current, advanced']));
                $writer->addRow(Row::fromValues(['frequency_response_fields', 'Pipe-separated existing detail names: '.implode(', ', array_keys(Question::FREQUENCY_ADVANCED_OPTIONAL_FIELDS))]));
                $writer->addRow(Row::fromValues(['response_category', 'General, Diagnostic & Preventative, Basic, Major, Orthodontics']));
                $writer->addRow(Row::fromValues(['secondary_field_key', 'Use the existing paired answer key; do not invent mappings.']));
            }
            $writer->addNewSheetAndMakeItCurrent()->setName('Existing Fields');
            $writer->addRow(Row::fromValues(['question_key', 'question', 'section_key', 'answer_type', 'secondary_field_key', 'secondary_input_type']));
            foreach (self::mappingOptions($headers === self::V3_HEADERS) as $key => $field) {
                $writer->addRow(Row::fromValues([$key, $field['prompt'], $field['section_key'], $field['input_type'], $field['secondary_field_key'] ?? '', $field['secondary_input_type'] ?? '']));
            }
        } finally {
            $writer->close();
        }
    }

    protected function checkArchive(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['upload' => 'The Excel workbook is invalid.']);
        }
        try {
            $size = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $size += $entry['size'];
                if ($zip->numFiles > 500 || $size > 20 * 1024 * 1024 || str_contains(strtolower($entry['name']), 'vbaproject')) {
                    throw ValidationException::withMessages(['upload' => 'Workbook is too complex or contains macros. Use the sample workbook.']);
                }
            }
        } finally {
            $zip->close();
        }
    }
}
