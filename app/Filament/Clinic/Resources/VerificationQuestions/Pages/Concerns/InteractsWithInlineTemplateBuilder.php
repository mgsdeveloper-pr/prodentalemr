<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages\Concerns;

use App\Models\VerificationFormQuestion;
use App\Support\ClinicTemplateBuilder;
use App\Support\VerificationTemplateImport;
use Livewire\Attributes\Locked;

trait InteractsWithInlineTemplateBuilder
{
    public bool $editorOpen = false;
    #[Locked] public ?int $editingQuestionId = null;
    public array $editor = [];
    #[Locked] public array $originalEditor = [];
    #[Locked] public array $pendingNavigation = [];
    public bool $showUnsaved = false;
    #[Locked] public array $removalIds = [];
    #[Locked] public array $removalSections = [];
    #[Locked] public array $removalLabels = [];
    public bool $showRemoval = false;

    public function editorDirty(): bool
    {
        return $this->editorOpen && $this->editor != $this->originalEditor;
    }

    protected function guardEditor(string $method, array $args = []): bool
    {
        if (! $this->editorDirty()) return false;
        $this->pendingNavigation = [$method, $args];
        $this->showUnsaved = true;
        return true;
    }

    public function resolveUnsaved(string $choice): void
    {
        abort_unless(in_array($choice, ['save', 'discard', 'stay'], true), 422);
        abort_unless($this->showUnsaved && count($this->pendingNavigation) === 2, 422);
        if ($choice === 'stay') { $this->showUnsaved = false; $this->pendingNavigation = []; return; }
        if ($choice === 'save') $this->saveInlineQuestion();
        else $this->resetInlineEditor();
        [$method, $args] = $this->pendingNavigation;
        $this->pendingNavigation = [];
        $this->showUnsaved = false;
        $this->{$method}(...$args);
    }

    protected function resetInlineEditor(): void
    {
        $this->editorOpen = false;
        $this->editingQuestionId = null;
        $this->editor = $this->originalEditor = [];
        $this->resetValidation();
    }

    public function cancelInlineQuestion(): void
    {
        if ($this->guardEditor('cancelInlineQuestion')) return;
        $this->resetInlineEditor();
    }

    public function editInlineQuestion(?int $id = null): void
    {
        if ($this->guardEditor('editInlineQuestion', [$id])) return;
        abort_unless($this->isDraftEditingOpen(), 403);
        $version = $this->getDisplayedClinicVersion();
        $question = $id ? $version->questions()->findOrFail($id) : null;
        $this->resetInlineEditor();
        $this->editingQuestionId = $id;
        $this->editor = array_replace([
            'prompt' => '', 'section_key' => $this->getSelectedBuilderSection()['key'] ?? '',
            'input_type' => 'text', 'select_options' => '', 'help_text' => '', 'placeholder' => '',
            'is_active' => true, 'is_required_for_audit' => false, 'has_note' => false, 'note_label' => '',
            'parent_question_id' => null, 'trigger_answer' => null,
            'answer_layout' => 'frequency', 'frequency_response_mode' => 'current', 'frequency_response_fields' => [],
        ], $question?->only(['prompt', 'section_key', 'input_type', 'select_options', 'help_text', 'placeholder',
            'is_active', 'is_required_for_audit', 'has_note', 'note_label', 'parent_question_id', 'trigger_answer',
            'answer_layout', 'frequency_response_mode', 'frequency_response_fields']) ?? []);
        $this->editor['frequency_response_fields'] ??= [];
        $this->editor['mapping'] = $question?->is_builtin ? 'keep' : 'standalone';
        $this->editor['confirm_disconnect'] = false;
        $this->editor['procedure_codes'] = implode('|', array_column($question?->procedure_tags ?? [], 'code'))
            ?: str_replace('/', '|', (string) $question?->code);
        $this->editor['code_system'] = ($question?->procedure_tags[0]['system'] ?? 'CDT');
        $this->originalEditor = $this->editor;
        $this->editorOpen = true;
        $this->builderView = 'questions';
    }

    public function saveInlineQuestion(): void
    {
        abort_unless($this->editorOpen && $this->isDraftEditingOpen(), 403);
        $question = app(ClinicTemplateBuilder::class)->save($this->getDisplayedClinicVersion()->id, $this->editingQuestionId, $this->editor);
        $this->selectedSectionKey = $question->section_key;
        $this->resetInlineEditor();
        $this->resetBuilderCaches();
        \Filament\Notifications\Notification::make()->title('Draft question saved')->success()->send();
    }

    public function getInlineMappings(): array
    {
        return collect(VerificationTemplateImport::mappingOptions(true))->mapWithKeys(fn ($field, $key) => [$key => $field['prompt'] ?? $key])->all();
    }

    public function getSourceDrafts()
    {
        $source = $this->getDisplayedClinicVersion();
        return $source ? \App\Models\VerificationTemplateVersion::where('scope', 'clinic')->where('clinic_id', $source->clinic_id)
            ->where('template_key', $source->template_key)->where('parent_version_id', $source->id)
            ->where('form_type', $this->builderFormType)->where('status', 'draft')->get()
            ->filter(fn ($draft) => $draft->canEditDirectly()) : collect();
    }

    public function continueSourceDraft(int $id): void
    {
        abort_unless($this->getSourceDrafts()->contains('id', $id), 404);
        $this->closeCreateDraftModal();
        $this->selectBuilderVersion($id);
    }

    public function getInlineParents()
    {
        return $this->getDisplayedClinicVersion()?->questions()->where('is_active', true)->where('input_type', 'yes_no')
            ->when($this->editingQuestionId, fn ($q) => $q->whereKeyNot($this->editingQuestionId))->get() ?? collect();
    }

    public function requestRemoval(?int $questionId = null): void
    {
        if ($this->guardEditor('requestRemoval', [$questionId])) return;
        abort_unless($this->isDraftEditingOpen(), 403);
        $version = $this->getDisplayedClinicVersion();
        $this->removalSections = [];
        if ($questionId) $questions = collect([$version->questions()->findOrFail($questionId)]);
        else {
            $key = $this->getSelectedBuilderSection()['key'] ?? '';
            $section = $version->sections()->where('section_key', $key)->firstOrFail();
            $this->removalSections = [$key, ...$version->sections()->where('parent_section_key', $key)->pluck('section_key')->all()];
            $questions = $version->questions()->whereIn('section_key', $this->removalSections)->get();
        }
        $this->removalIds = $questions->pluck('id')->all();
        $this->removalLabels = $questions->pluck('prompt')->all();
        $this->showRemoval = true;
    }

    public function confirmRemoval(): void
    {
        abort_unless($this->showRemoval, 403);
        app(ClinicTemplateBuilder::class)->remove($this->getDisplayedClinicVersion()->id, $this->removalIds, $this->removalSections);
        $this->showRemoval = false;
        $this->resetInlineEditor();
        $this->resetBuilderCaches();
    }

    public function returnToTemplates(): void
    {
        if ($this->guardEditor('returnToTemplates')) return;
        $this->redirect(\App\Filament\Clinic\Pages\VerificationSettings::getUrl(['section' => 'template-management']), navigate: true);
    }
}
