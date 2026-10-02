<?php

use App\Filament\Saas\Pages\ImportVerificationTemplate;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationTemplateVersion;
use App\Support\AdminClinicScope;
use App\Support\VerificationTemplateImport;
use App\Support\VerificationTemplateVersionService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create(['status' => true]);
    $this->user->assignRole('saas_admin');
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    $this->importer = app(VerificationTemplateImport::class);
    $this->rows = array_map(fn ($row) => array_combine(VerificationTemplateImport::HEADERS, $row), $this->importer->sampleRows());
});

it('normalizes only known seeded dropdown lists without splitting custom wording', function () {
    $question = new \App\Models\VerificationFormQuestion(['is_builtin' => true, 'field_key' => 'vf_insured_relation', 'input_type' => 'select', 'select_options' => 'Self, Spouse, Son, Daughter, Dependent']);
    expect($question->getSelectOptionValues())->toBe(['Self', 'Spouse', 'Son', 'Daughter', 'Dependent']);
    $question->is_builtin = false;
    expect($question->getSelectOptionValues())->toBe(['Self, Spouse, Son, Daughter, Dependent']);
    $question->is_builtin = true;
    $question->select_options = "Other, please specify\nNot confirmed";
    expect($question->getSelectOptionValues())->toBe(['Other, please specify', 'Not confirmed']);
});

it('round trips the sample workbook without shifting blank columns', function () {
    $path = tempnam(sys_get_temp_dir(), 'sample');
    try {
        $this->importer->writeWorkbook($path, $this->importer->sampleRows());
        $review = $this->importer->read($path, 'sample.xlsx');
        expect($review['errors'])->toBe([])
            ->and($review['rows'])->toHaveCount(3)
            ->and($review['rows'][0]['choices'])->toBe('')
            ->and($review['rows'][0]['form_type'])->toBe('both');
    } finally {
        unlink($path);
    }
});

function compoundImportRow(array $overrides = []): array
{
    return array_replace(array_fill_keys(VerificationTemplateImport::V3_HEADERS, ''), [
        'section_key' => 'custom_frequency', 'section_name' => 'Frequency & Percentage',
        'subsection_key' => 'custom_diagnostic', 'subsection_name' => 'Diagnostic & Preventative',
        'section_order' => '50', 'subsection_order' => '10', 'question_order' => '10',
        'question_key' => 'exam_benefit', 'question' => 'Regular Oral Exams',
        'answer_type' => 'frequency_row', 'required_for_audit' => 'no', 'form_type' => 'full_form',
        'format_version' => '3', 'question_purpose' => 'frequency', 'answer_layout' => 'frequency',
        'frequency_response_mode' => 'advanced', 'frequency_response_fields' => 'age_limit|notes',
        'response_category' => 'Diagnostic & Preventative',
    ], $overrides);
}

it('blocks active subsections below inactive parents and unreviewed empty sections', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Completeness', [compoundImportRow()]);
    $service = app(VerificationTemplateVersionService::class);
    $parent = $draft->sections()->whereNull('parent_section_key')->sole();
    $parent->update(['is_active' => false]);
    expect(fn () => $service->assertPublishable($draft))->toThrow(ValidationException::class, 'inactive parent');
    $parent->update(['is_active' => true]);
    $empty = $draft->sections()->create(['template_key' => 'template_3', 'section_key' => 'custom_empty', 'label' => 'Empty review', 'parent_section_key' => $parent->section_key, 'is_active' => true]);
    expect(fn () => $service->assertPublishable($draft))->toThrow(ValidationException::class, 'Review empty section');
    $empty->update(['allow_empty' => true]);
    $service->assertPublishable($draft);
    $copy = $service->createDraftFromPublished($draft);
    expect($copy->sections()->where('section_key', 'custom_empty')->sole()->allow_empty)->toBeTrue();
});

it('does not publish a template with no active questions even when empty sections are confirmed', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Empty template', [compoundImportRow()]);
    $draft->questions()->update(['is_active' => false]);
    $draft->sections()->update(['allow_empty' => true]);
    expect(fn () => app(VerificationTemplateVersionService::class)->assertPublishable($draft))->toThrow(ValidationException::class, 'at least one active question');
});

it('keeps unknown regrouped questions in Needs Mapping and requires resolution', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Unmapped', $this->rows);
    $draft->update(['form_type' => 'full_form']);
    $question = $draft->questions()->first();
    $question->update(['section_key' => 'custom_unrecognized', 'field_key' => 'unmapped_field']);
    app(\App\Support\VerificationTemplateHierarchy::class)->arrangeDraft($draft);
    expect($question->fresh()->section_key)->toBe('custom_layout_needs_mapping');
    expect(fn () => app(VerificationTemplateVersionService::class)->assertPublishable($draft))->toThrow(ValidationException::class, 'Needs Mapping');
    $snapshot = $draft->questions()->get()->toArray();
    app(\App\Support\VerificationTemplateHierarchy::class)->arrangeDraft($draft->fresh());
    expect($draft->questions()->get()->toArray())->toBe($snapshot);
    $question->refresh()->update(['section_key' => 'custom_layout_comments_answers']);
    $draft->sections()->where('section_key', '!=', 'custom_layout_needs_mapping')->update(['allow_empty' => true]);
    app(VerificationTemplateVersionService::class)->assertPublishable($draft->fresh());
});

it('corrects orthodontic coverage in copies without changing the source or lifetime maximum', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Ortho mapping', $this->rows);
    $question = $draft->questions()->first();
    $question->update(['is_builtin' => true, 'field_key' => 'vf_coverage_orthodontics_deductible_applies', 'input_type' => 'yes_no', 'secondary_field_key' => 'vf_ortho_lifetime_maximum', 'secondary_input_type' => 'percent']);
    $service = app(VerificationTemplateVersionService::class);
    expect(fn () => $service->assertPublishable($draft))->toThrow(ValidationException::class, 'lifetime maximum');
    $copy = $service->createDraftFromPublished($draft);
    $corrected = $copy->questions()->where('field_key', $question->field_key)->sole();
    expect($corrected->secondary_field_key)->toBe('vf_ortho_benefit')
        ->and($question->fresh()->secondary_field_key)->toBe('vf_ortho_lifetime_maximum');
    $pdf = new class extends \App\Support\VerificationResultPdf { public static function row($q, $state) { return static::mapQuestionRow($q, $state); } };
    $answer = $pdf::row($corrected, ['vf_coverage_orthodontics_deductible_applies' => 'Yes', 'vf_ortho_benefit' => 50, 'vf_ortho_lifetime_maximum' => 2000]);
    expect($answer['percent'])->toContain('50')->not->toContain('2000');
});

it('renders real preview controls with isolated answers and no patient writes', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Interactive preview', [compoundImportRow()]);
    $count = \App\Models\BillingWorkItem::count();
    $profiles = \App\Models\VerificationProfile::count();
    Livewire::test(\App\Filament\Saas\Resources\VerificationFormQuestions\Pages\ListVerificationFormQuestions::class)
        ->call('showTemplateVersionPreview', $draft->id, 'full_form')
        ->assertSee('Unsaved preview answers')
        ->assertSee('Regular Oral Exams')
        ->set('data.vf_ortho_benefit', '50')
        ->assertHasNoErrors();
    expect(\App\Models\BillingWorkItem::count())->toBe($count)
        ->and(\App\Models\VerificationProfile::count())->toBe($profiles);
});

it('opens publication review only after completeness checks and allows one subsection level', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Builder actions', [compoundImportRow()]);
    $page = Livewire::test(\App\Filament\Saas\Resources\VerificationFormQuestions\Pages\ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $draft->id)
        ->call('reviewSelectedTemplate')
        ->assertHasNoErrors()
        ->assertSet('mountedActions.0.name', 'publishDraftVersion');
    expect($draft->fresh()->status)->toBe('draft');
    $page->call('unmountAction')
        ->call('openTemplateSectionModal', 'custom_frequency')
        ->assertSet('showTemplateSectionModal', true)
        ->set('newTemplateSectionData.label', 'Review subsection')
        ->call('createSelectedTemplateSection')
        ->assertHasNoErrors()
        ->call('reviewSelectedTemplate')
        ->assertHasErrors(['template']);
    $section = $draft->sections()->where('label', 'Review subsection')->sole();
    expect($section->parent_section_key)->toBe('custom_frequency');
    $page->call('confirmEmptySection', $section->section_key)->assertHasNoErrors();
    expect($section->fresh()->allow_empty)->toBeTrue();
    expect($page->instance()->canAddSubSectionToSection($section->section_key))->toBeFalse();
    $page->call('closeTemplateVersionPanel')->assertDontSee('Publish This Draft');
    expect(collect($page->instance()->getCachedHeaderActions())->map(fn ($action) => $action->getName())->all())
        ->toBe(['importTemplate', 'createDraftVersion']);
});

it('groups consecutive frequency preview rows without repeating table headings', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Grouped preview', [
        compoundImportRow(),
        compoundImportRow(['question_key' => 'second_benefit', 'question' => 'Second reviewed benefit', 'question_order' => '20']),
    ]);
    $page = Livewire::test(\App\Filament\Saas\Resources\VerificationFormQuestions\Pages\ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $draft->id)
        ->call('selectEditorSection', 'custom_diagnostic')
        ->call('setTemplatePreviewFormType', 'full_form')
        ->assertSee('Regular Oral Exams')
        ->assertSee('Second reviewed benefit')
        ->assertHasNoErrors();
    expect(substr_count($page->html(), 'class="uel2-table uel2-benefit-table"'))->toBe(1);
});

it('renders template PDFs in each output mode without saving preview records', function (string $mode) {
    $draft = $this->importer->createDraft($this->user, null, 'PDF preview', [compoundImportRow()]);
    $q = $draft->questions()->sole();
    $before = [\App\Models\BillingWorkItem::count(), \App\Models\VerificationProfile::count(), \App\Models\VerificationCoverageCode::count()];
    $pdf = \App\Support\VerificationResultPdf::templatePreview($draft, 'full_form', [], [$q->id => ['coverage_percent' => 80, 'frequency' => 'Twice yearly']], $mode);
    expect($pdf)->toStartWith('%PDF-');
    expect([\App\Models\BillingWorkItem::count(), \App\Models\VerificationProfile::count(), \App\Models\VerificationCoverageCode::count()])->toBe($before);
})->with(['standard', 'custom_portrait', 'custom_landscape']);

it('hides conditional preview answers until their parent answer matches', function () {
    $parent = new \App\Models\VerificationFormQuestion(['id' => 1, 'is_builtin' => true, 'field_key' => 'vf_waiting_period', 'question_kind' => 'standard']);
    $child = new \App\Models\VerificationFormQuestion(['id' => 2, 'question_kind' => \App\Models\VerificationFormQuestion::QUESTION_KIND_CONDITIONAL, 'parent_question_id' => 1, 'trigger_answer' => 'Yes']);
    $parent->id = 1; $child->id = 2;
    $service = app(\App\Services\Verification\VerificationAuditService::class);
    expect($service->visibleInAnswerState($child, collect([$parent, $child]), []))->toBeFalse()
        ->and($service->visibleInAnswerState($child, collect([$parent, $child]), ['vf_waiting_period' => 'Yes']))->toBeTrue()
        ->and($service->visibleInAnswerState($child, collect([$parent, $child]), ['vf_waiting_period' => 'No']))->toBeFalse();
});

it('round trips compound answers without flattening their controls', function () {
    $row = compoundImportRow();
    $path = tempnam(sys_get_temp_dir(), 'v3');
    try {
        $this->importer->writeWorkbook($path, [array_values($row)], VerificationTemplateImport::V3_HEADERS);
        $review = $this->importer->read($path, 'v3.xlsx');
        expect($review['errors'])->toBe([]);
        $draft = $this->importer->createDraft($this->user, null, 'Compound', $review['rows']);
        $q = $draft->questions()->sole();
        expect($draft->uses_section_layout)->toBeTrue()->and($draft->sections()->count())->toBe(2)
            ->and($q->input_type)->toBe('frequency_row')->and($q->frequency_response_fields)->toBe(['age_limit', 'notes'])
            ->and($q->frequencyResponseConfiguration()['primary_fields'])->toBe(['coverage_percent', 'frequency']);
        app(VerificationTemplateVersionService::class)->assertPublishable($draft);
        $copy = app(VerificationTemplateVersionService::class)->createDraftFromPublished($draft);
        expect($copy->uses_section_layout)->toBeTrue()->and($copy->sections()->count())->toBe(2)
            ->and($copy->questions()->sole()->frequencyResponseConfiguration())->toBe($q->frequencyResponseConfiguration());
    } finally { unlink($path); }
});

it('keeps mapped answers in the chosen hierarchy and imports paired coverage controls', function () {
    $row = compoundImportRow(['question_key' => 'vf_coverage_diagnostic_deductible_applies', 'question' => 'Diagnostic coverage', 'answer_type' => 'yes_no', 'answer_layout' => '', 'frequency_response_mode' => '', 'frequency_response_fields' => '', 'response_category' => '', 'secondary_field_key' => 'vf_coverage_diagnostic', 'secondary_input_type' => 'percent']);
    $review = $this->importer->reviewMappings([$row], [$row['question_key']], 'full_form');
    expect($review['errors'])->toBe([])->and($review['rows'][0]['section_key'])->toBe('custom_frequency');
    $draft = $this->importer->createDraft($this->user, null, 'Pair', $review['rows']);
    $q = $draft->questions()->sole();
    expect($q->is_builtin)->toBeTrue()->and($q->secondary_field_key)->toBe('vf_coverage_diagnostic');
    $pdf = new class extends \App\Support\VerificationResultPdf { public static function row($q, $state) { return static::mapQuestionRow($q, $state); } };
    $result = $pdf::row($q, ['vf_coverage_diagnostic_deductible_applies' => 'Yes', 'vf_coverage_diagnostic' => 80]);
    expect($result['kind'])->toBe('coverage_matrix')->and($result['percent'])->toContain('80');
    expect($this->importer->validateRows([[...$row, 'secondary_field_key' => 'vf_patient_dob']])['errors'])->not->toBeEmpty();
    expect($this->importer->validateRows([[...$row, 'secondary_field_key' => '']])['errors'])->not->toBeEmpty();
});

it('rejects incompatible compound settings and hierarchy cycles', function () {
    foreach ([['answer_layout' => 'unknown'], ['frequency_response_fields' => 'unknown'], ['response_category' => 'Unknown'], ['subsection_key' => 'custom_frequency'], ['answer_type' => 'text']] as $override) {
        expect($this->importer->validateRows([compoundImportRow($override)])['errors'])->not->toBeEmpty();
    }
});

it('keeps explicit orthodontic controls after renaming and moving the question', function () {
    $row = compoundImportRow(['answer_layout' => 'ortho_payment', 'response_category' => 'Orthodontics', 'question' => 'How is Ortho Paid?']);
    $draft = $this->importer->createDraft($this->user, null, 'Payment', [$row]);
    $q = $draft->questions()->sole();
    $before = $q->frequencyResponseConfiguration();
    $q->update(['prompt' => 'Policy funding and installments', 'section_key' => 'custom_other']);
    expect($q->fresh()->frequencyResponseConfiguration())->toBe($before)
        ->and($before['field_options']['payment_guideline'])->toHaveKey('Dental')->toHaveKey('Medical');
});

it('creates the seven section hierarchy without changing the source answer definitions', function () {
    $rows = array_map(fn ($r) => [...$r, 'form_type' => 'full_form'], $this->rows);
    $source = $this->importer->createDraft($this->user, null, 'Original', $rows);
    $before = $source->questions()->get()->toArray();
    $draft = app(VerificationTemplateVersionService::class)->createDraftFromSource($source, ['form_type' => 'full_form']);
    app(\App\Support\VerificationTemplateHierarchy::class)->arrangeDraft($draft);
    expect($draft->sections()->whereNull('parent_section_key')->count())->toBe(7)
        ->and($draft->questions()->count())->toBe(count($before))
        ->and($source->questions()->get()->toArray())->toBe($before)
        ->and($source->fresh()->uses_section_layout)->toBeFalse()
        ->and($draft->is_active)->toBeFalse();
    $options = \App\Models\VerificationFormQuestion::topLevelSectionOptionsForTemplate('template_3', null, $draft->id);
    expect($options)->toHaveCount(7);
    $snapshot = app(VerificationTemplateVersionService::class)->snapshot($draft);
    expect($snapshot['version']['uses_section_layout'])->toBeTrue();
    expect(\App\Support\VerificationTemplateHierarchy::definitions('short_form'))->toHaveCount(7);
});

it('previews ordered parent sections and paired answers only for supported forms', function () {
    $rows = array_map(fn ($r) => [...$r, 'form_type' => 'full_form'], $this->rows);
    $draft = $this->importer->createDraft($this->user, null, 'Preview hierarchy', $rows);
    app(\App\Support\VerificationTemplateHierarchy::class)->arrangeDraft($draft);
    $question = $draft->questions()->first();
    $question->update([
        'section_key' => 'custom_layout_coverage_category_answers',
        'input_type' => 'yes_no',
        'secondary_field_key' => 'vf_preventive_percentage',
        'secondary_input_type' => 'percentage',
    ]);
    $page = new \App\Filament\Saas\Resources\VerificationFormQuestions\Pages\ListVerificationFormQuestions;
    $page->selectedTemplateVersionId = $draft->id;
    $detail = $page->getSelectedTemplateVersionDetail();
    expect($detail['supports_full'])->toBeTrue()
        ->and($detail['supports_short'])->toBeFalse()
        ->and($detail['short_question_count'])->toBeNull();
    $preview = collect($detail['preview_sections']);
    expect($preview->where('is_subsection', false)->pluck('title')->all())
        ->toBe(array_map(fn ($section) => $section[0], array_values(\App\Support\VerificationTemplateHierarchy::definitions('full_form'))));
    expect($preview->first()['title'])->toBe('Patient / Insurance Information');
    $coverage = $preview->firstWhere('key', 'custom_layout_coverage_category_answers');
    expect($coverage['questions'][0]['secondary_input_type'])->not->toBeNull();
    $page->setTemplatePreviewFormType('short_form');
    expect($page->templatePreviewFormType)->toBe('full_form');
});

it('round trips v2 hierarchy and procedure tags without changing question wording', function () {
    \App\Models\AdaProcedureCode::updateOrCreate(['procedure_code' => 'D0120'], ['description' => 'Directory wording', 'is_active' => true, 'lifecycle_status' => 'active', 'source_year' => 2026]);
    $values = ['template_3_frequency_percentage', 'exam_frequency', 'How often is D0120 covered?', 'text', 'no', '', 'full_form', '2', 'Frequency & Percentage', 'custom_exams', 'Exams', '70', '71', '10', 'CDT', 'D0120', 'frequency', 'individual'];
    $path = tempnam(sys_get_temp_dir(), 'v2');
    try {
        $this->importer->writeWorkbook($path, [$values], VerificationTemplateImport::V2_HEADERS);
        $review = $this->importer->read($path, 'v2.xlsx');
        expect($review['errors'])->toBe([]);
        $draft = $this->importer->createDraft($this->user, null, 'V2', $review['rows']);
        $question = $draft->questions()->sole();
        expect($question->prompt)->toBe($values[2])->and($question->section_key)->toBe('custom_exams')
            ->and($question->procedure_tags[0]['code'])->toBe('D0120')
            ->and($question->procedure_tags[0]['status'])->toBe('directory_match')
            ->and($draft->sections()->where('section_key', 'custom_exams')->sole()->parent_section_key)->toBe('template_3_frequency_percentage');
        app(VerificationTemplateVersionService::class)->assertPublishable($draft);
        $copy = app(VerificationTemplateVersionService::class)->createDraftFromPublished($draft);
        expect($copy->questions()->sole()->procedure_tags)->toBe($question->procedure_tags);
    } finally {
        unlink($path);
    }
});

it('flags conflicting codes and preserves unverified codes only in drafts', function () {
    $row = array_combine(VerificationTemplateImport::V2_HEADERS, ['template_3_frequency_percentage', 'test_code', 'Does D0120 share frequency with D0140?', 'yes_no', 'no', '', 'full_form', '2', 'Frequency', 'custom_exams', 'Exams', '70', '71', '10', 'CDT', 'D0120', 'frequency', 'shared_frequency']);
    expect($this->importer->validateRows([$row])['errors'])->not->toBeEmpty();
    $row['question'] = 'Medical procedure coverage';
    $row['procedure_codes'] = '99213';
    $row['code_system'] = 'CPT';
    $row['code_relationship'] = 'individual';
    $draft = $this->importer->createDraft($this->user, null, 'Unverified', [$row]);
    expect($draft->questions()->sole()->procedure_tags[0]['status'])->toBe('unverified');
    expect(fn () => app(VerificationTemplateVersionService::class)->publishDraft($draft))->toThrow(ValidationException::class);
});

it('rejects conflicting subsection definitions and ambiguous individual code groups', function () {
    $row = array_combine(VerificationTemplateImport::V2_HEADERS, ['template_3_frequency_percentage', 'exam', 'D0120 and D0140', 'text', 'no', '', 'full_form', '2', 'Frequency', 'custom_exams', 'Exams', '70', '71', '10', '', '', 'frequency', 'individual']);
    expect($this->importer->validateRows([$row])['errors'])->not->toBeEmpty();
    $row['code_relationship'] = 'shared_frequency';
    expect($this->importer->validateRows([$row, [...$row, 'question_key' => 'other', 'subsection_name' => 'Other']])['errors'])->not->toBeEmpty();
});

it('maps known intake fields to shared answers and keeps unmatched questions custom', function () {
    $rows = [
        ['template_3_patient_subscriber', 'short_patient_name', 'Patient name', 'text', 'no', '', 'short_form'],
        ['template_3_patient_subscriber', 'short_clinic_name', 'Clinic name', 'text', 'no', '', 'short_form'],
        ['template_3_insurance', 'short_provider_in_network', 'Is the provider in network?', 'yes_no', 'no', '', 'short_form'],
        ['template_3_service_history', 'short_fluoride_history', 'Fluoride history', 'textarea', 'no', '', 'short_form'],
    ];
    $rows = array_map(fn ($row) => array_combine(VerificationTemplateImport::HEADERS, $row), $rows);
    $draft = $this->importer->createDraft($this->user, null, 'Mapped short form', $rows);
    app(VerificationTemplateVersionService::class)->assertPublishable($draft);
    $questions = $draft->questions()->get()->keyBy('field_key');
    expect($questions['vf_patient_full_name']->is_builtin)->toBeTrue()
        ->and($questions['context_clinic_name']->is_builtin)->toBeTrue()
        ->and($questions['vf_network_status']->input_type)->toBe('select')
        ->and($questions['short_fluoride_history']->is_builtin)->toBeFalse();
    $pdf = new class extends \App\Support\VerificationResultPdf {
        public static function field($question) { return static::resolveField($question); }
    };
    expect($pdf::field($questions['vf_patient_full_name']))->toBe('vf_patient_full_name')
        ->and($pdf::field($questions['context_clinic_name']))->toBe('context_clinic_name')
        ->and($pdf::field($questions['short_fluoride_history']))->toBe('custom_question_'.$questions['short_fluoride_history']->id);
    $page = new class extends \App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest {
        public function mappedRow($question): array
        {
            return $this->mapManagedTemplateQuestionToRow($question);
        }
    };
    foreach ($questions as $question) {
        expect($page->mappedRow($question)['field'])->toBe($pdf::field($question));
    }
});

it('rejects duplicate system mappings and unapproved system fields', function () {
    $row = array_combine(VerificationTemplateImport::HEADERS, ['template_3_patient_subscriber', 'short_patient_name', 'Patient name', 'text', 'no', '', 'short_form']);
    expect($this->importer->validateRows([$row, [...$row, 'question_key' => 'vf_patient_full_name']])['errors'])->toHaveCount(1);
    expect($this->importer->validateRows([[...$row, 'question_key' => 'vf_unknown']])['errors'])->toHaveCount(1);
    expect($this->importer->validateRows([[...$row, 'question_key' => 'vf_patient_full_name', 'section_key' => 'template_3_insurance']])['errors'])->toHaveCount(1);
});

it('preserves uploaded wording while binding to an existing answer', function () {
    $row = array_combine(VerificationTemplateImport::HEADERS, ['template_3_patient_subscriber', 'patient_label', 'Patient legal name as shown on the policy', 'text', 'no', '', 'full_form']);
    $review = $this->importer->reviewMappings([$row], ['vf_patient_full_name'], 'full_form');
    expect($review['errors'])->toBe([])->and($review['rows'][0]['question'])->toBe($row['question']);
    $draft = $this->importer->createDraft($this->user, null, 'Preserved text', $review['rows']);
    expect($draft->questions()->sole()->prompt)->toBe($row['question']);
});

it('allows repeated wording in different sections but rejects oversized prompts', function () {
    $row = array_combine(VerificationTemplateImport::HEADERS, ['template_3_insurance', 'plan_notes', 'Notes', 'textarea', 'no', '', 'full_form']);
    $second = [...$row, 'section_key' => 'template_3_service_history', 'question_key' => 'history_notes'];
    expect($this->importer->reviewMappings([$row, $second], ['custom', 'custom'], 'full_form')['errors'])->toBe([]);
    expect($this->importer->validateRows([[...$row, 'question' => str_repeat('x', 256)]])['errors'])->not->toBeEmpty();
});

it('preserves section hierarchy labels ordering and repeated unbound questions when copying', function () {
    $source = $this->importer->createDraft($this->user, null, 'Source', $this->rows);
    $source->sections()->where('section_key', 'template_3_insurance')->update(['label' => 'Policy Details', 'sort_order' => 901]);
    foreach ([['custom_parent', null], ['custom_child', 'custom_parent']] as [$key, $parent]) {
        $source->sections()->create(['template_key' => $source->template_key, 'section_key' => $key, 'parent_section_key' => $parent, 'label' => $key, 'sort_order' => 902, 'is_builtin' => false, 'is_active' => true]);
    }
    for ($i = 0; $i < 2; $i++) {
        $source->questions()->create(['template_key' => $source->template_key, 'section_key' => 'custom_child', 'prompt' => 'Notes', 'form_type' => 'both', 'input_type' => 'text', 'is_active' => true, 'is_builtin' => false]);
    }
    $draft = app(VerificationTemplateVersionService::class)->createDraftFromPublished($source);
    expect($draft->sections()->where('section_key', 'custom_child')->sole()->parent_section_key)->toBe('custom_parent')
        ->and($draft->sections()->where('section_key', 'template_3_insurance')->sole()->label)->toBe('Policy Details')
        ->and($draft->sections()->where('section_key', 'template_3_insurance')->sole()->sort_order)->toBe(901)
        ->and($draft->questions()->where('section_key', 'custom_child')->count())->toBe(2);
});

it('requires explicit mappings and rejects the other form without discarding rows', function () {
    $row = array_combine(VerificationTemplateImport::HEADERS, ['template_3_patient_subscriber', 'patient_label', 'Patient name', 'text', 'no', '', 'short_form']);
    expect($this->importer->reviewMappings([$row], [], 'short_form')['errors'])->not->toBeEmpty();
    $review = $this->importer->reviewMappings([$row], ['vf_patient_full_name'], 'short_form');
    expect($review['errors'])->toBe([])
        ->and($review['rows'][0]['question_key'])->toBe('vf_patient_full_name')
        ->and($review['rows'][0]['form_type'])->toBe('short_form');
    $wrong = $this->importer->reviewMappings([$row], ['vf_patient_full_name'], 'full_form');
    expect($wrong['rows'])->toHaveCount(1)->and($wrong['errors'])->not->toBeEmpty();
});

it('rejects duplicate destinations and custom copies of system answers', function () {
    $row = array_combine(VerificationTemplateImport::HEADERS, ['template_3_patient_subscriber', 'short_patient_name', 'Patient name', 'text', 'no', '', 'short_form']);
    expect($this->importer->reviewMappings([$row], ['custom'], 'short_form')['errors'])->not->toBeEmpty();
    expect($this->importer->reviewMappings([$row, [...$row, 'question_key' => 'another']], ['vf_patient_full_name', 'vf_patient_full_name'], 'short_form')['errors'])->not->toBeEmpty();
});

it('publishes a short form without replacing the active full form', function () {
    $active = $this->importer->createDraft($this->user, null, 'Combined', $this->rows);
    $active->update(['status' => 'published', 'is_active' => true, 'is_working_draft' => false]);
    $rows = array_map(fn ($row) => [...$row, 'form_type' => 'short_form'], $this->rows);
    $draft = $this->importer->createDraft($this->user, null, 'Short', $rows);
    app(VerificationTemplateVersionService::class)->publishDraft($draft, clinicVisibility: VerificationTemplateVersion::CLINIC_VISIBILITY_DEFAULT, activate: true);
    expect($active->fresh()->is_active)->toBeTrue()
        ->and($active->fresh()->active_full_form)->toBeTrue()->and($active->fresh()->active_short_form)->toBeFalse()
        ->and($draft->fresh()->active_short_form)->toBeTrue()->and($draft->fresh()->active_full_form)->toBeFalse();
});

it('persists import receipts and reuses a draft on a retried request', function () {
    $token = (string) \Illuminate\Support\Str::uuid();
    $first = $this->importer->createDraft($this->user, null, 'Retry', $this->rows, $token);
    $retry = $this->importer->createDraft($this->user, null, 'Retry', $this->rows, $token);
    expect($retry->id)->toBe($first->id)
        ->and(\App\Models\VerificationTemplateImportReceipt::count())->toBe(1);
    expect(fn () => $this->importer->createDraft($this->user, null, 'Changed', $this->rows, $token))->toThrow(ValidationException::class);
});

it('requires a separate publishing grant even when editing is allowed', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Permission test', $this->rows);
    $editor = User::factory()->create(['status' => true]);
    $editor->assignRole('saas_manager');
    foreach (['view', 'update'] as $action) {
        $editor->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('saas.template_management.'.$action, 'web'));
    }
    $this->actingAs($editor);
    expect($editor->canManageVerificationTemplateSections())->toBeTrue();
    expect(fn () => app(VerificationTemplateVersionService::class)->publishDraft($draft))->toThrow(HttpException::class);
    foreach (['view', 'update'] as $action) {
        $editor->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('saas.template_publishing.'.$action, 'web'));
    }
    expect(app(VerificationTemplateVersionService::class)->publishDraft($draft)->status)->toBe('published');
});

it('retains the receipt when an unused imported draft is deleted and prevents replay', function () {
    $token = (string) \Illuminate\Support\Str::uuid();
    $draft = $this->importer->createDraft($this->user, null, 'Disposable draft', $this->rows, $token);
    app(VerificationTemplateVersionService::class)->deleteUnusedDraft($draft);
    expect(\App\Models\VerificationTemplateImportReceipt::sole()->template_version_id)->toBeNull();
    expect(fn () => $this->importer->createDraft($this->user, null, 'Disposable draft', $this->rows, $token))->toThrow(ValidationException::class);
});

it('blocks publication of orphaned and circular section hierarchies', function () {
    $draft = $this->importer->createDraft($this->user, null, 'Hierarchy test', $this->rows);
    $section = $draft->sections()->create(['template_key' => $draft->template_key, 'section_key' => 'custom_child', 'parent_section_key' => 'missing', 'label' => 'Child', 'sort_order' => 100, 'is_active' => true]);
    $service = app(VerificationTemplateVersionService::class);
    expect(fn () => $service->assertPublishable($draft))->toThrow(ValidationException::class);
    $section->update(['parent_section_key' => 'custom_child']);
    expect(fn () => $service->assertPublishable($draft))->toThrow(ValidationException::class);
    $section->update(['parent_section_key' => null]);
    $service->assertPublishable($draft);
});

it('validates duplicate keys unsupported sections types and choices', function () {
    $rows = $this->rows;
    $rows[1]['question_key'] = $rows[0]['question_key'];
    $rows[1]['section_key'] = 'unknown';
    $rows[1]['answer_type'] = 'invalid';
    $rows[2]['choices'] = 'Same|Same';
    expect($this->importer->validateRows($rows)['errors'])->toHaveCount(4);
});

it('rejects missing headers and formulas', function () {
    $file = UploadedFile::fake()->createWithContent('invalid.csv', "question,answer\nHello,text");
    expect(fn () => $this->importer->read($file->getRealPath(), 'invalid.csv'))->toThrow(ValidationException::class);
    $rows = $this->rows;
    $rows[0]['question'] = '=HYPERLINK("https://example.test")';
    expect($this->importer->validateRows($rows)['errors'])->not->toBeEmpty();
});

it('previews a csv without writes then creates only one unpublished draft', function () {
    $csv = implode(',', VerificationTemplateImport::HEADERS)."\n";
    foreach ($this->importer->sampleRows() as $row) {
        $csv .= implode(',', $row)."\n";
    }
    $published = $this->importer->createDraft($this->user, null, 'Existing', $this->rows);
    $published->update(['status' => 'published', 'is_active' => true, 'is_working_draft' => false]);
    $count = VerificationTemplateVersion::count();
    $page = Livewire::test(ImportVerificationTemplate::class)
        ->set('formType', 'full_form')
        ->set('templateName', 'Imported Benefits')
        ->set('upload', UploadedFile::fake()->createWithContent('sample.csv', $csv))
        ->call('preview')->assertHasNoErrors()
        ->set('mappings', ['custom', 'custom', 'custom'])
        ->call('validateMappings')->assertSee('3 questions validated')
        ->set('confirmed', true);
    expect(VerificationTemplateVersion::count())->toBe($count);
    $page->call('importDraft')->assertHasNoErrors()->assertSee('Draft Created')
        ->call('importDraft')->assertHasNoErrors();
    expect(VerificationTemplateVersion::count())->toBe($count + 1)
        ->and($published->fresh()->is_active)->toBeTrue()
        ->and($published->fresh()->questions()->count())->toBe(3);
    $draft = VerificationTemplateVersion::latest('id')->first();
    expect($draft->status)->toBe('draft')->and($draft->is_active)->toBeFalse()
        ->and($draft->form_type)->toBe('full_form')
        ->and($draft->questions()->count())->toBe(3);
    app(VerificationTemplateVersionService::class)->assertPublishable($draft);
});

it('registers the import routes and renders the master page', function () {
    $this->get('/saas/import-verification-template')->assertSuccessful()->assertSee('Download Sample');
    expect(route('filament.admin.pages.import-verification-template'))->toContain('/verification/import-verification-template');
    expect(route('filament.clinic.pages.import-verification-template'))->toContain('/clinic/import-verification-template');
});

it('requires approval and invalidates it after changing a mapping', function (string $type) {
    $csv = implode(',', VerificationTemplateImport::HEADERS)."\n"
        ."template_3_patient_subscriber,short_patient_name,Patient name,text,no,,{$type}\n";
    $page = Livewire::test(ImportVerificationTemplate::class)
        ->set('formType', $type)
        ->set('templateName', 'Approval test')
        ->set('upload', UploadedFile::fake()->createWithContent('sample.csv', $csv))
        ->call('preview')->assertHasNoErrors()->assertSee('1 questions validated')
        ->call('importDraft')->assertHasErrors('confirmed');
    expect(VerificationTemplateVersion::count())->toBe(0);
    $page->set('confirmed', true)
        ->set('mappings.0', 'vf_subscriber_name')
        ->assertSet('confirmed', false)->assertSet('review', [])
        ->call('validateMappings')->assertSee('1 questions validated')
        ->set('confirmed', true)
        ->call('importDraft')->assertHasNoErrors()->assertSee('Draft Created');
    $draft = VerificationTemplateVersion::sole();
    expect($draft->form_type)->toBe($type)
        ->and($draft->status)->toBe('draft')
        ->and($draft->questions()->sole()->field_key)->toBe('vf_subscriber_name');
})->with(['short_form', 'full_form']);

it('clears the previous review when the form type changes', function () {
    $csv = implode(',', VerificationTemplateImport::HEADERS)."\n"
        ."template_3_patient_subscriber,short_patient_name,Patient name,text,no,,short_form\n";
    Livewire::test(ImportVerificationTemplate::class)
        ->set('formType', 'short_form')->set('templateName', 'Changed form')
        ->set('upload', UploadedFile::fake()->createWithContent('sample.csv', $csv))
        ->call('preview')->set('confirmed', true)
        ->set('formType', 'full_form')
        ->assertSet('review', [])->assertSet('sourceRows', [])->assertSet('confirmed', false)
        ->call('importDraft')->assertHasErrors('upload');
    expect(VerificationTemplateVersion::count())->toBe(0);
});

it('refuses an invalid preview without creating a draft', function () {
    Livewire::test(ImportVerificationTemplate::class)
        ->set('templateName', 'No preview')
        ->call('importDraft')
        ->assertHasErrors('upload');
    expect(VerificationTemplateVersion::count())->toBe(0);
    $rows = array_fill(0, VerificationTemplateImport::MAX_ROWS + 1, $this->rows[0]);
    expect($this->importer->validateRows($rows)['errors'])->toContain('Include between 1 and 500 questions.');
});

it('blocks clinic switching and unauthorized cross clinic imports', function () {
    $org = Organization::create(['name' => 'Import Group', 'owner_name' => 'Owner', 'email' => 'import@example.test', 'status' => true]);
    $a = Clinic::create(['organization_id' => $org->id, 'clinic_name' => 'Import A', 'clinic_code' => 'IMP-A', 'status' => true]);
    $b = Clinic::create(['organization_id' => $org->id, 'clinic_name' => 'Import B', 'clinic_code' => 'IMP-B', 'status' => true]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    session([AdminClinicScope::SESSION_KEY => $a->id]);
    $b->refresh();
    $draft = $this->importer->createDraft($this->user, $a, 'Clinic Import', $this->rows);
    expect($draft->scope)->toBe('clinic')
        ->and($draft->clinic_id)->toBe($a->id)
        ->and($draft->organization_id)->toBe($org->id)
        ->and($draft->questions()->where('clinic_id', $a->id)->count())->toBe(3)
        ->and($b->fresh()->verification_default_form_template)->toBe($b->verification_default_form_template);
    $page = Livewire::test(App\Filament\Admin\Pages\ImportVerificationTemplate::class);
    session([AdminClinicScope::SESSION_KEY => $b->id]);
    $page->call('importDraft')->assertForbidden();
    $staff = User::factory()->create(['organization_id' => $org->id, 'clinic_id' => $a->id, 'status' => true]);
    $staff->assignRole('clinic_admin');
    expect(fn () => $this->importer->createDraft($staff, $b, 'Forbidden', $this->rows))
        ->toThrow(HttpException::class);
});
