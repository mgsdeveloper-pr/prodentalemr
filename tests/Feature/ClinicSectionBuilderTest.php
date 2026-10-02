<?php

use App\Filament\Clinic\Resources\VerificationQuestions\Pages\ListVerificationQuestions;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateImport;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $org = Organization::create(['name' => 'Builder Test', 'owner_name' => 'Test', 'email' => 'builder@example.test', 'phone' => '5551002000', 'status' => true]);
    $this->clinic = Clinic::create(['organization_id' => $org->id, 'clinic_name' => 'Builder Clinic', 'clinic_code' => 'BUILDER', 'status' => true, 'verification_services_enabled' => true]);
    $this->user = User::factory()->create(['organization_id' => $org->id, 'clinic_id' => $this->clinic->id, 'status' => true]);
    $this->user->assignRole('clinic_admin');
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('clinic'));
    $this->version = VerificationTemplateVersion::create([
        'scope' => 'clinic', 'clinic_id' => $this->clinic->id, 'organization_id' => $org->id,
        'template_key' => 'template_3', 'version_number' => 1, 'name' => 'Full Approved',
        'form_type' => 'full_form', 'status' => 'published', 'is_active' => true, 'uses_section_layout' => true,
    ]);
    $this->version->sections()->create(['template_key' => 'template_3', 'clinic_id' => $this->clinic->id, 'organization_id' => $org->id, 'section_key' => 'custom_existing', 'label' => 'Existing Section', 'is_active' => true, 'sort_order' => 10]);
    Livewire::withQueryParams(['version' => $this->version->id]);
    $this->row = array_replace(array_fill_keys(VerificationTemplateImport::V3_HEADERS, ''), [
        'format_version' => '3', 'section_key' => 'custom_existing', 'section_name' => 'Existing Section',
        'section_order' => '10', 'question_order' => '10', 'question_key' => 'new_question',
        'question' => 'Clinic specific question?', 'answer_type' => 'text', 'required_for_audit' => 'no',
        'form_type' => 'full_form', 'question_purpose' => 'other',
    ]);
});

it('rechecks both active slots before archiving', function ($slot) {
    $action = app(\App\Actions\Verification\ArchiveClinicTemplateVersionAction::class);
    $stale = $this->version->fresh();
    \App\Models\VerificationTemplateVersion::whereKey($this->version->id)->update([
        'is_active' => false, 'active_full_form' => $slot === 'full_form', 'active_short_form' => $slot === 'short_form',
    ]);
    expect(fn () => $action->execute($this->user, $this->clinic, $stale))->toThrow(ValidationException::class);
    expect($this->version->fresh()->status)->toBe('published');
})->with(['full_form', 'short_form']);

it('blocks archiving another clinics template even for an administrator', function () {
    $this->user->assignRole('saas_admin');
    $other = Clinic::create(['organization_id' => $this->clinic->organization_id,
        'clinic_name' => 'Other archive clinic', 'clinic_code' => 'OTHER-ARCHIVE', 'status' => true]);
    $this->version->update(['is_active' => false]);
    expect(fn () => app(\App\Actions\Verification\ArchiveClinicTemplateVersionAction::class)
        ->execute($this->user, $other, $this->version))->toThrow(ValidationException::class);
    expect($this->version->fresh()->status)->toBe('published');
});

it('shows archived versions in history without offering them for activation', function () {
    $this->version->update(['is_active' => false]);
    app(\App\Actions\Verification\ArchiveClinicTemplateVersionAction::class)->execute($this->user, $this->clinic, $this->version);
    $page = Livewire::test(\App\Filament\Clinic\Pages\VerificationSettings::class)
        ->call('showSettingsSection', 'template-management')->assertDontSee('Full Approved')
        ->set('showTemplateHistory', true)->assertSee('Full Approved')->assertSee('Archived');
    expect($page->instance()->getFormTemplateOptions('full_form'))->toBe([]);
});

it('filters versions by form and preserves existing sections in a named draft', function () {
    $short = $this->version->replicate();
    $short->fill(['name' => 'Short Approved', 'form_type' => 'short_form', 'version_number' => 2, 'active_full_form' => false, 'active_short_form' => true])->save();
    $page = Livewire::test(ListVerificationQuestions::class)->assertSee('Full Approved')->assertDontSee('Short Approved')
        ->call('selectBuilderForm', 'short_form')->assertSee('Short Approved')->assertDontSee('Full Approved')
        ->call('selectBuilderForm', 'full_form')->call('openCreateDraftModal')
        ->set('draftName', 'Full Reviewed Draft')->call('submitCreateDraftVersion')->assertHasNoErrors()
        ->assertSee('Full Reviewed Draft')->assertSee('Existing Section')->assertSee('Upload Questions');
    $draft = VerificationTemplateVersion::findOrFail($page->get('selectedTemplateVersionId'));
    expect($draft->status)->toBe('draft')->and($draft->form_type)->toBe('full_form')
        ->and($draft->sections()->pluck('section_key')->all())->toBe(['custom_existing'])
        ->and($this->version->fresh()->status)->toBe('published');
});

it('appends once and does not alter the published source or its structure', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $service = app(VerificationTemplateImport::class);
    $token = (string) \Illuminate\Support\Str::uuid();
    $service->appendToSection($this->user, $draft, 'custom_existing', 'full_form', [$this->row], $token);
    $service->appendToSection($this->user, $draft, 'custom_existing', 'full_form', [$this->row], $token);
    expect($draft->questions()->count())->toBe(1)->and($this->version->questions()->count())->toBe(0)
        ->and($draft->sections()->count())->toBe(1)
        ->and($draft->questions()->sole()->input_type)->toBe('text');
    expect(fn () => $service->appendToSection($this->user, $draft, 'custom_existing', 'full_form', [$this->row], (string) \Illuminate\Support\Str::uuid()))->toThrow(ValidationException::class);
});

it('rejects published targets wrong sections and wrong form types atomically', function () {
    $service = app(VerificationTemplateImport::class);
    expect(fn () => $service->appendToSection($this->user, $this->version, 'custom_existing', 'full_form', [$this->row], (string) \Illuminate\Support\Str::uuid()))->toThrow(ValidationException::class);
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    foreach ([['custom_missing', 'full_form'], ['custom_existing', 'short_form']] as [$section, $form]) {
        expect(fn () => $service->appendToSection($this->user, $draft, $section, $form, [$this->row], (string) \Illuminate\Support\Str::uuid()))->toThrow(ValidationException::class);
    }
    expect($draft->questions()->count())->toBe(0);
});

it('returns to template selection when no version was chosen without provisioning anything', function () {
    $count = VerificationTemplateVersion::count();
    Livewire::withQueryParams([])->test(ListVerificationQuestions::class)
        ->assertRedirect(\App\Filament\Clinic\Pages\VerificationSettings::getUrl(['section' => 'template-management']));
    expect(VerificationTemplateVersion::count())->toBe($count);
});

it('reviews an uploaded section file and requires confirmation before adding', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $stream = fopen('php://temp', 'r+');
    fputcsv($stream, VerificationTemplateImport::V3_HEADERS, ',', '"', '');
    fputcsv($stream, array_values($this->row), ',', '"', '');
    rewind($stream);
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('questions.csv', stream_get_contents($stream));
    fclose($stream);
    Livewire::test(ListVerificationQuestions::class)->call('selectBuilderVersion', $draft->id)
        ->call('openSectionUpload')->call('downloadSectionSample')->assertFileDownloaded('section-questions.csv')
        ->set('upload', $file)->call('reviewSectionUpload')->assertHasNoErrors()->assertSet('sectionUploadErrors', [])
        ->assertSee('Clinic specific question?')->call('saveSectionUpload')->assertHasErrors('confirmSectionUpload')
        ->set('confirmSectionUpload', true)->call('saveSectionUpload')->assertHasNoErrors()->assertSet('showSectionUpload', false);
    expect($draft->questions()->count())->toBe(1);
});

it('does not permit another clinic version to be selected', function () {
    $other = $this->version->replicate();
    $other->fill(['clinic_id' => null, 'scope' => 'master', 'version_number' => 3])->save();
    expect(fn () => Livewire::test(ListVerificationQuestions::class)->call('selectBuilderVersion', $other->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('copies the selected historical version rather than a different active version', function () {
    $old = $this->version;
    $old->update(['is_active' => false]);
    $active = $old->replicate();
    $active->fill(['version_number' => 2, 'name' => 'New Active', 'is_active' => true])->save();
    $page = Livewire::test(ListVerificationQuestions::class)->call('selectBuilderVersion', $old->id)
        ->call('openCreateDraftModal')->set('draftName', 'Historical Copy')->call('submitCreateDraftVersion')->assertHasNoErrors();
    $draft = VerificationTemplateVersion::findOrFail($page->get('selectedTemplateVersionId'));
    expect($draft->source_version_id)->toBe($old->id)->and($draft->sections()->count())->toBe(1);
});

it('rechecks draft status at upload confirmation', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $service = app(VerificationTemplateImport::class);
    expect($service->reviewSection($draft, 'custom_existing', 'full_form', [$this->row])['errors'])->toBe([]);
    $draft->update(['status' => 'published']);
    expect(fn () => $service->appendToSection($this->user, $draft, 'custom_existing', 'full_form', [$this->row], (string) \Illuminate\Support\Str::uuid()))->toThrow(ValidationException::class);
    expect($draft->questions()->count())->toBe(0);
});

it('offers an editable copy for a locked draft instead of reopening it', function () {
    $service = app(\App\Support\VerificationTemplateVersionService::class);
    $locked = $service->createDraftFromPublished($this->version);
    $service->createDraftFromPublished($locked);
    $page = Livewire::test(ListVerificationQuestions::class)->call('selectBuilderVersion', $locked->id)
        ->assertSee('Create Draft From This Version')->assertDontSee('Open Draft')->assertDontSee('Published template is protected.')
        ->call('beginTemplateChange', 'reorder')->assertSet('showCreateDraftModal', true)
        ->set('draftName', 'Editable replacement')->call('submitCreateDraftVersion')->assertHasNoErrors();
    $copy = VerificationTemplateVersion::findOrFail($page->get('selectedTemplateVersionId'));
    expect($copy->id)->not->toBe($locked->id)->and($copy->canEditDirectly())->toBeTrue()
        ->and($locked->fresh()->status)->toBe('draft');
});

it('uses interactive preview controls without changing saved questions', function () {
    $q = $this->version->questions()->create([
        'template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'organization_id' => $this->clinic->organization_id, 'section_key' => 'custom_existing',
        'field_key' => 'network_test', 'prompt' => 'Network choice?', 'input_type' => 'select',
        'select_options' => "In network\nOut of network", 'is_active' => true, 'form_type' => 'full_form',
    ]);
    $before = $q->fresh()->getAttributes();
    Livewire::test(ListVerificationQuestions::class)->call('setBuilderView', 'preview')
        ->assertSee('In network')->assertSee('Out of network')->assertSee('Form Preview')->assertDontSee('Unsaved Preview')
        ->set('data.custom_question_'.$q->id, 'In network')->assertHasNoErrors();
    expect($q->fresh()->getAttributes())->toBe($before);
});

it('counts the actual hierarchy in settings', function () {
    $page = new class extends \App\Filament\Clinic\Pages\VerificationSettings {
        public function counts($version) { return $this->approvedTemplateThreeSectionCounts($version, 1); }
    };
    expect($page->counts($this->version))->toBe(['main' => 1, 'sub' => 0]);
});

it('renders frequency rows in the clinic full-form preview', function () {
    $q = $this->version->questions()->create([
        'template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'organization_id' => $this->clinic->organization_id, 'section_key' => 'custom_existing',
        'field_key' => 'frequency_preview', 'prompt' => 'Frequency preview question',
        'input_type' => 'frequency_row', 'answer_layout' => 'frequency',
        'is_active' => true, 'form_type' => 'full_form',
    ]);
    $before = $q->fresh()->getAttributes();
    Livewire::test(ListVerificationQuestions::class)->call('setBuilderView', 'preview')
        ->assertSee('Form Preview')->assertDontSee('Unsaved Preview')->assertSee('Frequency preview question')->assertHasNoErrors()
        ->assertSee('Complete Form Preview')->assertSee('1 visible questions')
        ->assertDontSee('Search questions in this section')->assertDontSee('Upload Questions')
        ->call('setBuilderView', 'questions')->assertSee('Search questions in this section');
    expect($q->fresh()->getAttributes())->toBe($before);
});

it('reviews an existing version when no form is active without activating it', function () {
    $this->version->update(['is_active' => false]);
    $page = Livewire::test(ListVerificationQuestions::class)->assertSee('Full Approved')->assertDontSee('No template selected');
    expect($page->instance()->getVersionSummary()['displayed_id'])->toBe($this->version->id)
        ->and($this->version->fresh()->is_active)->toBeFalse();
});

it('opens the exact editable draft without showing another template picker', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    Livewire::withQueryParams(['version' => $draft->id])->test(ListVerificationQuestions::class)
        ->assertSet('selectedTemplateVersionId', $draft->id)->assertSet('showDraft', true)
        ->assertSee('Review &amp; Publish', false)->assertDontSee('id="builder-version"', false)
        ->call('reviewPublishing')->assertHasErrors('template')->assertSet('showPublishReview', false);
    expect($draft->fresh()->status)->toBe('draft');
});

it('does not substitute another version for an invalid requested version', function () {
    Livewire::withQueryParams(['version' => 999999])->test(ListVerificationQuestions::class)->assertNotFound();
});

it('keeps child questions out of the selected parent question list', function () {
    $this->version->sections()->create([
        'template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'organization_id' => $this->clinic->organization_id, 'section_key' => 'custom_child',
        'parent_section_key' => 'custom_existing', 'label' => 'Child Section', 'is_active' => true,
    ]);
    $this->version->questions()->create([
        'template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'organization_id' => $this->clinic->organization_id, 'section_key' => 'custom_child',
        'field_key' => 'child_only', 'prompt' => 'Child question only', 'input_type' => 'text',
        'is_active' => true, 'form_type' => 'full_form',
    ]);
    Livewire::test(ListVerificationQuestions::class)->assertDontSee('Child question only')
        ->call('selectBuilderSection', 'custom_child')->assertSee('Child question only');
});

it('retains template list filters when returning from a builder', function () {
    $settings = \App\Filament\Clinic\Pages\VerificationSettings::class;
    Livewire::withQueryParams(['section' => 'template-management'])->test($settings)
        ->set('templateListSearch', 'Full Approved')->set('templateListForm', 'full_form')
        ->assertSee('Full Approved');
    Livewire::withQueryParams(['section' => 'template-management'])->test($settings)
        ->assertSet('templateListSearch', 'Full Approved')->assertSet('templateListForm', 'full_form');
});

it('edits mapped draft wording while preserving the source mapping and order', function () {
    $source = $this->version->questions()->create([
        'template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'organization_id' => $this->clinic->organization_id, 'section_key' => 'custom_existing',
        'field_key' => 'patient_name', 'prompt' => 'Patient name', 'input_type' => 'text',
        'is_builtin' => true, 'is_active' => true, 'form_type' => 'full_form', 'sort_order' => 20,
    ]);
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $question = $draft->questions()->sole();
    $before = $question->getAttributes();
    Livewire::withQueryParams(['template_version_id' => $draft->id])
        ->test(\App\Filament\Clinic\Resources\VerificationQuestions\Pages\EditVerificationQuestion::class, ['record' => $question->getRouteKey()])
        ->fillForm(['prompt' => 'Patient full name', 'help_text' => 'As shown on the policy', 'placeholder' => 'Full name'])
        ->set('data.field_key', 'insurance_name')->set('data.input_type', 'currency')
        ->call('save')->assertHasNoFormErrors();
    expect($question->fresh()->prompt)->toBe('Patient full name')
        ->and($question->fresh()->field_key)->toBe($before['field_key'])
        ->and($question->fresh()->input_type)->toBe('text')
        ->and($question->fresh()->sort_order)->toBe(20)
        ->and($source->fresh()->prompt)->toBe('Patient name');
    expect(\App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource::canDelete($question->fresh()))->toBeFalse();
    expect(\App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource::canEdit($source->fresh()))->toBeFalse();
});

it('blocks saving mapped changes if the draft becomes published after opening the editor', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $question = $draft->questions()->create([
        'template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'organization_id' => $this->clinic->organization_id, 'section_key' => 'custom_existing',
        'field_key' => 'patient_name', 'prompt' => 'Patient name', 'input_type' => 'text',
        'is_builtin' => true, 'is_active' => true, 'form_type' => 'full_form',
    ]);
    $page = Livewire::withQueryParams(['template_version_id' => $draft->id])
        ->test(\App\Filament\Clinic\Resources\VerificationQuestions\Pages\EditVerificationQuestion::class, ['record' => $question->getRouteKey()])
        ->fillForm(['prompt' => 'Changed after publication']);
    $draft->update(['status' => 'published']);
    $page->call('save')->assertForbidden();
    expect($question->fresh()->prompt)->toBe('Patient name');
});

it('does not grant mapped question editing to a different clinic', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $question = new \App\Models\VerificationFormQuestion(['clinic_id' => $this->clinic->id + 100, 'is_builtin' => true]);
    $question->setRelation('templateVersion', $draft);
    expect(\App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource::canEdit($question))->toBeFalse();
});

it('edits inline and prompts before leaving unsaved changes', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $page = Livewire::withQueryParams(['version' => $draft->id])->test(ListVerificationQuestions::class)
        ->call('editInlineQuestion')->set('editor.prompt', 'New clinic answer')
        ->call('setBuilderView', 'preview')->assertSet('showUnsaved', true)->assertSet('builderView', 'questions')
        ->call('resolveUnsaved', 'stay')->assertSet('showUnsaved', false)->assertSet('editorOpen', true)
        ->call('setBuilderView', 'preview')->call('resolveUnsaved', 'save')->assertHasNoErrors()
        ->assertSet('editorOpen', false)->assertSet('builderView', 'preview');
    expect($draft->questions()->sole()->prompt)->toBe('New clinic answer');
    expect($this->version->questions()->count())->toBe(0);
});

it('requires explicit disconnection for an incompatible mapped answer type', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $question = $draft->questions()->create([
        'clinic_id' => $this->clinic->id, 'organization_id' => $this->clinic->organization_id,
        'template_key' => 'template_3', 'section_key' => 'custom_existing', 'field_key' => 'vf_patient_full_name',
        'prompt' => 'Patient name', 'input_type' => 'text', 'is_builtin' => true, 'is_active' => true, 'form_type' => 'full_form',
    ]);
    $semantic = $question->semantic_key;
    Livewire::withQueryParams(['version' => $draft->id])->test(ListVerificationQuestions::class)
        ->call('editInlineQuestion', $question->id)->set('editor.input_type', 'date')->call('saveInlineQuestion')
        ->assertHasErrors('editor.input_type')->set('editor.mapping', 'standalone')->call('saveInlineQuestion')
        ->assertHasErrors('editor.confirm_disconnect')->set('editor.confirm_disconnect', true)->call('saveInlineQuestion')->assertHasNoErrors();
    expect($question->fresh()->is_builtin)->toBeFalse()->and($question->fresh()->input_type)->toBe('date')
        ->and($question->fresh()->semantic_key)->not->toBe($semantic);
});

it('guards switching form type while a question has unsaved edits', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    Livewire::withQueryParams(['version' => $draft->id])->test(ListVerificationQuestions::class)
        ->call('editInlineQuestion')->set('editor.prompt', 'Unfinished question')
        ->call('selectBuilderForm', 'short_form')->assertSet('showUnsaved', true)
        ->assertSet('builderFormType', 'full_form')->call('resolveUnsaved', 'discard')
        ->assertSet('builderFormType', 'short_form')->assertSet('editorOpen', false);
    expect($draft->questions()->count())->toBe(0);
});

it('requires a removal review and blocks deleting a conditional parent', function () {
    $draft = app(\App\Support\VerificationTemplateVersionService::class)->createDraftFromPublished($this->version);
    $parent = $draft->questions()->create([
        'clinic_id' => $this->clinic->id, 'template_key' => 'template_3', 'section_key' => 'custom_existing',
        'field_key' => 'parent', 'prompt' => 'Parent', 'input_type' => 'yes_no', 'is_active' => true,
    ]);
    $child = $parent->replicate();
    $child->fill(['field_key' => 'child', 'prompt' => 'Child', 'parent_question_id' => $parent->id, 'question_kind' => 'conditional', 'trigger_answer' => 'yes'])->save();
    Livewire::withQueryParams(['version' => $draft->id])->test(ListVerificationQuestions::class)
        ->call('requestRemoval', $parent->id)->assertSet('showRemoval', true)->call('confirmRemoval')->assertHasErrors('removal');
    expect($draft->questions()->count())->toBe(2);
});

it('copies both currently active masters at registration without duplicating them on retry', function () {
    foreach (['full_form', 'short_form'] as $form) {
        $master = \App\Models\VerificationTemplateVersion::create([
            'scope' => 'master', 'template_key' => 'template_3', 'name' => 'Approved '.$form,
            'form_type' => $form, 'status' => 'published', 'is_active' => true,
            'active_full_form' => $form === 'full_form', 'active_short_form' => $form === 'short_form',
            'clinic_visibility' => 'default_for_new_clinics', 'uses_section_layout' => true, 'version_number' => 3,
        ]);
        $master->questions()->create(['template_key' => 'template_3', 'section_key' => 'template_3_patient_subscriber',
            'field_key' => $form.'_question', 'prompt' => 'Approved question', 'input_type' => 'text', 'form_type' => $form, 'is_active' => true]);
    }
    $clinic = \App\Models\Clinic::create(['organization_id' => $this->clinic->organization_id, 'clinic_name' => 'New Clinic', 'clinic_code' => 'NEW', 'status' => true]);
    app(\App\Support\VerificationTemplateVersionService::class)->provisionRegisteredClinic($clinic);
    $versions = \App\Models\VerificationTemplateVersion::where('clinic_id', $clinic->id)->get();
    expect($versions)->toHaveCount(2)->and($versions->where('active_full_form', true))->toHaveCount(1)
        ->and($versions->where('active_short_form', true))->toHaveCount(1);
    foreach ($versions as $version) expect($version->sourceVersion->version_number)->toBe(3)->and($version->questions()->count())->toBe(1);
});

it('does not fabricate a master when registration has no active forms', function () {
    $clinic = \App\Models\Clinic::create(['organization_id' => $this->clinic->organization_id, 'clinic_name' => 'Pending Clinic', 'clinic_code' => 'PENDING', 'status' => true]);
    expect(\App\Models\VerificationTemplateVersion::where('clinic_id', $clinic->id)->count())->toBe(0);
    expect(fn () => app(\App\Support\VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($clinic, 'template_3', 'full_form'))
        ->toThrow(ValidationException::class);
    expect(\App\Models\VerificationTemplateVersion::where('scope', 'master')->count())->toBe(0);
});

it('publishes an internal master without replacing the released full form', function () {
    $this->user->assignRole('saas_admin');
    $master = VerificationTemplateVersion::create([
        'scope' => 'master', 'template_key' => 'template_3', 'name' => 'Released full',
        'form_type' => 'full_form', 'status' => 'published', 'is_active' => true,
        'active_full_form' => true, 'active_short_form' => false,
        'clinic_visibility' => 'default_for_new_clinics', 'version_number' => 1,
    ]);
    $master->questions()->create([
        'template_key' => 'template_3', 'section_key' => 'template_3_patient_subscriber',
        'field_key' => 'master_question', 'prompt' => 'Question', 'input_type' => 'text',
        'form_type' => 'full_form', 'is_active' => true,
    ]);
    $service = app(\App\Support\VerificationTemplateVersionService::class);
    $draft = $service->createDraftFromPublished($master);
    $published = $service->publishDraft($draft, 'Internal review', null, VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN);
    expect($published->status)->toBe('published')->and($published->is_active)->toBeFalse()
        ->and($published->active_full_form)->toBeFalse()->and($master->fresh()->active_full_form)->toBeTrue();
});

it('publishes a clinic draft without activation unless explicitly confirmed', function () {
    $this->version->questions()->create(['template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'section_key' => 'custom_existing', 'field_key' => 'release_test', 'prompt' => 'Release question',
        'input_type' => 'text', 'form_type' => 'full_form', 'is_active' => true]);
    $service = app(\App\Support\VerificationTemplateVersionService::class);
    $draft = $service->createDraftFromPublished($this->version);
    Livewire::withQueryParams(['version' => $draft->id])->test(ListVerificationQuestions::class)
        ->call('reviewPublishing')->assertSet('showPublishReview', true)
        ->call('publishDraftVersion')->assertHasNoErrors();
    expect($draft->fresh()->status)->toBe('published')->and($draft->fresh()->is_active)->toBeFalse()
        ->and($this->version->fresh()->active_full_form)->toBeTrue();
    $next = $service->createDraftFromPublished($draft->fresh());
    Livewire::withQueryParams(['version' => $next->id])->test(ListVerificationQuestions::class)
        ->call('reviewPublishing')->call('publishDraftVersion', [], true)->assertHasNoErrors();
    expect($next->fresh()->active_full_form)->toBeTrue()->and($this->version->fresh()->active_full_form)->toBeFalse();
});

it('rejects a stale publish and activate review without publishing the draft', function () {
    $this->version->questions()->create(['template_key' => 'template_3', 'clinic_id' => $this->clinic->id,
        'section_key' => 'custom_existing', 'field_key' => 'stale_test', 'prompt' => 'Release question',
        'input_type' => 'text', 'form_type' => 'full_form', 'is_active' => true]);
    $service = app(\App\Support\VerificationTemplateVersionService::class);
    $draft = $service->createDraftFromPublished($this->version);
    $page = Livewire::withQueryParams(['version' => $draft->id])->test(ListVerificationQuestions::class)->call('reviewPublishing');
    $this->version->update(['active_full_form' => false, 'is_active' => false]);
    $page->call('publishDraftVersion', [], true)->assertHasErrors('activation');
    expect($draft->fresh()->status)->toBe('draft');
});
