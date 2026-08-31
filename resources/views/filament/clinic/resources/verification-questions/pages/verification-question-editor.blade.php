<x-filament-panels::page>
    @php
        $isEditing = str_contains(strtolower($this->getSubmitButtonLabel()), 'save');
        $editorTitle = $isEditing ? 'Edit Question' : 'Add Question';
        $sectionContext = $this->getSectionContextLabels();
        $orderCards = $this->getSectionQuestionOrderCards();
        $placementMode = $this->data['order_position'] ?? 'bottom';
        $placementReference = (int) ($this->data['order_reference_id'] ?? 0);
    @endphp

    <style>
        .pd-question-editor { display: flex; flex-direction: column; gap: 18px; }
        .pd-question-context { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 18px; border: 1px solid #cfe4e1; border-radius: 8px; background: #f4fbfa; }
        .pd-question-context__path { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-width: 0; }
        .pd-question-context__segment { color: #0f172a; font-size: 13px; font-weight: 750; }
        .pd-question-context__separator { color: #94a3b8; font-size: 13px; }
        .pd-question-editor__grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 400px); gap: 20px; align-items: start; }
        .pd-question-editor__main { min-width: 0; }
        .pd-question-editor__aside { position: sticky; top: 88px; min-width: 0; overflow: hidden; border: 1px solid #dbe4ee; border-radius: 8px; background: #fff; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05); }
        .pd-question-editor__aside-list { display: grid; gap: 8px; max-height: 54vh; overflow-y: auto; padding: 12px; }
        .pd-question-order-card { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
        .pd-question-editor__actions { position: sticky; z-index: 15; bottom: 0; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 13px 16px; border: 1px solid #dbe4ee; border-radius: 8px; background: rgba(255, 255, 255, 0.97); box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.08); backdrop-filter: blur(10px); }
        @media (max-width: 1100px) {
            .pd-question-editor__grid { grid-template-columns: 1fr; }
            .pd-question-editor__aside { position: static; }
            .pd-question-editor__aside-list { max-height: 420px; }
        }
        @media (max-width: 640px) {
            .pd-question-context, .pd-question-editor__actions { align-items: flex-start; flex-direction: column; }
            .pd-question-editor__actions > div:last-child { display: grid !important; grid-template-columns: 1fr; width: 100%; }
        }
    </style>

    <div class="pd-question-editor">
        <section class="pd-question-context" aria-label="Question location">
            <div style="min-width: 0;">
                <div style="margin-bottom: 6px; color: #0f766e; font-size: 10px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">Editing working draft</div>
                <div class="pd-question-context__path">
                    <span class="pd-question-context__segment">{{ $this->getSelectedClinicName() }}</span>
                    <span class="pd-question-context__separator">/</span>
                    @forelse ($sectionContext as $label)
                        <span class="pd-question-context__segment">{{ $label }}</span>
                        @unless ($loop->last)<span class="pd-question-context__separator">/</span>@endunless
                    @empty
                        <span class="pd-question-context__segment">Choose a section</span>
                    @endforelse
                </div>
            </div>
            <div style="flex: 0 0 auto; text-align: right;">
                <div style="font-size: 12px; font-weight: 800; color: #0f172a;">{{ $this->getWorkingDraftName() }}</div>
                <div style="margin-top: 3px; font-size: 11px; color: #64748b;">The active template changes only when this draft is published.</div>
            </div>
        </section>

        <form wire:submit.prevent="{{ $this->getSubmitMethodName() }}" class="pd-question-editor" novalidate>
            <div class="pd-question-editor__grid">
                <main class="pd-question-editor__main">
                    <div style="margin-bottom: 14px;">
                        <h2 style="margin: 0; color: #0f172a; font-size: 22px; font-weight: 800;">{{ $editorTitle }}</h2>
                        <p style="margin: 5px 0 0; color: #64748b; font-size: 13px; line-height: 1.6;">Confirm the location, define the response, and choose where the question should appear.</p>
                    </div>
                    {{ $this->form }}
                </main>

                <aside class="pd-question-editor__aside" aria-label="Question placement">
                    <header style="padding: 16px 18px; border-bottom: 1px solid #edf2f7;">
                        <div style="color: #0f766e; font-size: 10px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">Question placement</div>
                        <h3 style="margin: 6px 0 0; color: #0f172a; font-size: 18px; font-weight: 800;">Existing questions</h3>
                        <p style="margin: 5px 0 0; color: #64748b; font-size: 12px; line-height: 1.55;">Place the question at the top, bottom, or beside a specific question.</p>
                    </header>

                    @if (! filled($this->data['section_key'] ?? null))
                        <div style="margin: 14px; padding: 15px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; color: #64748b; font-size: 13px; line-height: 1.6;">Choose a section to load its questions.</div>
                    @else
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 12px; border-bottom: 1px solid #edf2f7;">
                            <button type="button" wire:click="setPlacement('top')" style="padding: 9px 10px; border: 1px solid {{ $placementMode === 'top' ? '#14b8a6' : '#dbe4ee' }}; border-radius: 7px; background: {{ $placementMode === 'top' ? '#f0fdfa' : '#fff' }}; color: {{ $placementMode === 'top' ? '#0f766e' : '#475569' }}; font-size: 12px; font-weight: 800; cursor: pointer;">Place at top</button>
                            <button type="button" wire:click="setPlacement('bottom')" style="padding: 9px 10px; border: 1px solid {{ $placementMode === 'bottom' ? '#14b8a6' : '#dbe4ee' }}; border-radius: 7px; background: {{ $placementMode === 'bottom' ? '#f0fdfa' : '#fff' }}; color: {{ $placementMode === 'bottom' ? '#0f766e' : '#475569' }}; font-size: 12px; font-weight: 800; cursor: pointer;">Place at bottom</button>
                        </div>
                        <div style="padding: 10px 12px; border-bottom: 1px solid #edf2f7; background: #f8fafc; color: #475569; font-size: 12px; line-height: 1.5;">{{ $this->getPlacementSummaryLabel() }}</div>
                        <div class="pd-question-editor__aside-list">
                            @forelse ($orderCards as $card)
                                <article class="pd-question-order-card">
                                    <div style="padding: 11px 12px; color: #0f172a; font-size: 12px; font-weight: 700; line-height: 1.5;">{{ $card['prompt'] }}</div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 7px; padding: 0 10px 10px;">
                                        <button type="button" wire:click="setPlacement('above', {{ $card['id'] }})" style="padding: 7px 8px; border: 1px solid {{ $placementMode === 'above' && $placementReference === $card['id'] ? '#14b8a6' : '#dbe4ee' }}; border-radius: 6px; background: {{ $placementMode === 'above' && $placementReference === $card['id'] ? '#f0fdfa' : '#fff' }}; color: {{ $placementMode === 'above' && $placementReference === $card['id'] ? '#0f766e' : '#475569' }}; font-size: 11px; font-weight: 800; cursor: pointer;">Before</button>
                                        <button type="button" wire:click="setPlacement('below', {{ $card['id'] }})" style="padding: 7px 8px; border: 1px solid {{ $placementMode === 'below' && $placementReference === $card['id'] ? '#14b8a6' : '#dbe4ee' }}; border-radius: 6px; background: {{ $placementMode === 'below' && $placementReference === $card['id'] ? '#f0fdfa' : '#fff' }}; color: {{ $placementMode === 'below' && $placementReference === $card['id'] ? '#0f766e' : '#475569' }}; font-size: 11px; font-weight: 800; cursor: pointer;">After</button>
                                    </div>
                                </article>
                            @empty
                                <div style="padding: 15px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; color: #64748b; font-size: 13px; line-height: 1.6;">No questions exist in this section. This question will become the first one.</div>
                            @endforelse
                        </div>
                    @endif
                </aside>
            </div>

            <footer class="pd-question-editor__actions">
                <div style="color: #64748b; font-size: 12px; line-height: 1.5;">Existing verification requests and completed snapshots will not change.</div>
                <div style="display: flex; align-items: center; gap: 9px;">
                    <a href="{{ $this->getCancelUrl() }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 108px; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; color: #334155; font-size: 12px; font-weight: 800; text-decoration: none;">Cancel</a>
                    <button type="button" wire:click="{{ $this->getSecondarySubmitMethodName() }}" wire:loading.attr="disabled" wire:target="{{ $this->getSecondarySubmitMethodName() }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 150px; padding: 10px 15px; border: 1px solid #94a3b8; border-radius: 7px; background: #fff; color: #334155; font-size: 12px; font-weight: 800; cursor: pointer;">
                        <span wire:loading.remove wire:target="{{ $this->getSecondarySubmitMethodName() }}">{{ $this->getSecondarySubmitButtonLabel() }}</span>
                        <span wire:loading wire:target="{{ $this->getSecondarySubmitMethodName() }}">Saving...</span>
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="{{ $this->getSubmitMethodName() }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 10px 15px; border: 1px solid #0f766e; border-radius: 7px; background: #0f766e; color: #fff; font-size: 12px; font-weight: 800; cursor: pointer;">
                        <span wire:loading.remove wire:target="{{ $this->getSubmitMethodName() }}">{{ $this->getSubmitButtonLabel() }}</span>
                        <span wire:loading wire:target="{{ $this->getSubmitMethodName() }}">Saving...</span>
                    </button>
                </div>
            </footer>
        </form>
    </div>
</x-filament-panels::page>
