<x-filament-panels::page>
    @php
        $currentVisibility = $this->getCurrentVisibilityLabel();
        $currentAnswerType = $this->getCurrentAnswerTypeLabel();
        $promptPreview = $this->getCurrentPromptPreview();
        $orderCards = $this->getSectionQuestionOrderCards();
        $builderTitle = str_contains(strtolower($this->getSubmitButtonLabel()), 'save') ? 'Update Question' : 'Create Question';
    @endphp

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 22px 26px; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr); gap: 24px; align-items: start;">
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; width: max-content; padding: 8px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f766e; font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">
                        Question Management
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <h2 style="margin: 0; font-size: 28px; line-height: 1.15; font-weight: 800; color: #0f172a;">Create and organize verification questions</h2>
                        <p style="margin: 0; max-width: 760px; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Build the question, choose its template section, and confirm its position against existing questions in one workspace.
                        </p>
                    </div>
                </div>

                <div style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; padding: 20px 22px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05); display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #94a3b8;">Clinic scope</div>
                            <div style="margin-top: 6px; font-size: 20px; font-weight: 800; color: #0f172a;">{{ $this->getSelectedClinicName() }}</div>
                        </div>
                        <span style="display: inline-flex; align-items: center; padding: 8px 12px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;">
                            {{ $this->getSubmitButtonLabel() }}
                        </span>
                    </div>

                    <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                        <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Live preview</div>
                        <div style="font-size: 18px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $promptPreview }}</div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;">
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Section</div>
                            <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $this->getCurrentSectionLabel() }}</div>
                        </div>
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Visibility</div>
                            <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $currentVisibility }}</div>
                        </div>
                        <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Answer type</div>
                            <div style="margin-top: 8px; font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">{{ $currentAnswerType }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <form wire:submit="{{ $this->getSubmitMethodName() }}" style="display: flex; flex-direction: column; gap: 20px;">
            <div style="display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(360px, 0.75fr); gap: 20px; align-items: start;">
                <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06); overflow: hidden;">
                    <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">{{ $builderTitle }}</div>
                        <div style="margin-top: 6px; font-size: 15px; line-height: 1.7; color: #64748b;">Scope, template, question text, answer type, notes, and status.</div>
                    </div>
                    <div style="padding: 22px;">
                        {{ $this->form }}
                    </div>
                </section>

                <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05); overflow: hidden;">
                    <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Created Questions</div>
                        <h3 style="margin: 8px 0 0; font-size: 21px; font-weight: 800; color: #0f172a;">Questions in this section</h3>
                        <div style="margin-top: 6px; font-size: 14px; line-height: 1.7; color: #64748b;">See what already exists and place this question exactly where it belongs.</div>
                    </div>
                    <div style="padding: 18px 22px; display: grid; gap: 12px;">
                        @if (! filled($this->data['section_key'] ?? null))
                            <div style="padding: 16px 18px; border-radius: 18px; border: 1px dashed #cbd5e1; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                                Choose a section first. The existing questions in that section will appear here so you can place this one above, below, at the top, or at the bottom.
                            </div>
                        @else
                            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                                <button
                                    type="button"
                                    wire:click="setPlacement('top')"
                                    style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 12px; border-radius: 14px; border: 1px solid {{ ($this->data['order_position'] ?? 'bottom') === 'top' ? '#f59e0b' : '#dbe4ee' }}; background: {{ ($this->data['order_position'] ?? 'bottom') === 'top' ? '#fff7ed' : '#ffffff' }}; color: {{ ($this->data['order_position'] ?? 'bottom') === 'top' ? '#b45309' : '#475569' }}; font-size: 13px; font-weight: 800;"
                                >
                                    Move to top
                                </button>
                                <button
                                    type="button"
                                    wire:click="setPlacement('bottom')"
                                    style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 12px; border-radius: 14px; border: 1px solid {{ ($this->data['order_position'] ?? 'bottom') === 'bottom' ? '#f59e0b' : '#dbe4ee' }}; background: {{ ($this->data['order_position'] ?? 'bottom') === 'bottom' ? '#fff7ed' : '#ffffff' }}; color: {{ ($this->data['order_position'] ?? 'bottom') === 'bottom' ? '#b45309' : '#475569' }}; font-size: 13px; font-weight: 800;"
                                >
                                    Move to bottom
                                </button>
                            </div>

                            <div style="padding: 14px 16px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fafc; font-size: 13px; line-height: 1.7; color: #475569;">
                                {{ $this->getPlacementSummaryLabel() }}
                            </div>

                            @if (empty($orderCards))
                                <div style="padding: 16px 18px; border-radius: 18px; border: 1px dashed #cbd5e1; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                                    No questions have been added in this section yet. Save this question and it will appear here.
                                </div>
                            @else
                                <div style="max-height: 520px; overflow-y: auto; padding-right: 4px; display: grid; grid-template-columns: 1fr; gap: 12px;">
                                    @foreach ($orderCards as $card)
                                        <div style="border: 1px solid #e2e8f0; border-radius: 18px; background: #ffffff; overflow: hidden;">
                                            <div style="padding: 14px 16px; border-bottom: 1px solid #edf2f7;">
                                                <div style="font-size: 13px; font-weight: 700; line-height: 1.65; color: #0f172a;">{{ $card['prompt'] }}</div>
                                                <div style="margin-top: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #94a3b8;">
                                                    Current order {{ $card['sort_order'] }}
                                                </div>
                                            </div>
                                            <div style="padding: 12px 16px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                                                <button
                                                    type="button"
                                                    wire:click="setPlacement('above', {{ $card['id'] }})"
                                                    style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 12px; border-radius: 14px; border: 1px solid {{ (($this->data['order_position'] ?? null) === 'above' && (int) ($this->data['order_reference_id'] ?? 0) === $card['id']) ? '#f59e0b' : '#dbe4ee' }}; background: {{ (($this->data['order_position'] ?? null) === 'above' && (int) ($this->data['order_reference_id'] ?? 0) === $card['id']) ? '#fff7ed' : '#ffffff' }}; color: {{ (($this->data['order_position'] ?? null) === 'above' && (int) ($this->data['order_reference_id'] ?? 0) === $card['id']) ? '#b45309' : '#475569' }}; font-size: 12px; font-weight: 800;"
                                                >
                                                    Above this
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="setPlacement('below', {{ $card['id'] }})"
                                                    style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 12px; border-radius: 14px; border: 1px solid {{ (($this->data['order_position'] ?? null) === 'below' && (int) ($this->data['order_reference_id'] ?? 0) === $card['id']) ? '#f59e0b' : '#dbe4ee' }}; background: {{ (($this->data['order_position'] ?? null) === 'below' && (int) ($this->data['order_reference_id'] ?? 0) === $card['id']) ? '#fff7ed' : '#ffffff' }}; color: {{ (($this->data['order_position'] ?? null) === 'below' && (int) ($this->data['order_reference_id'] ?? 0) === $card['id']) ? '#b45309' : '#475569' }}; font-size: 12px; font-weight: 800;"
                                                >
                                                    Below this
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </section>

            </div>

            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05); overflow: hidden;">
                    <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Save Actions</div>
                    </div>
                    <div style="padding: 18px 22px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                        <a
                            href="{{ $this->getCancelUrl() }}"
                            style="display: inline-flex; align-items: center; justify-content: center; min-width: 128px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 700; text-decoration: none;"
                        >
                            Cancel
                        </a>
                        <button
                            type="submit"
                            style="display: inline-flex; align-items: center; justify-content: center; min-width: 148px; padding: 11px 18px; border: 0; border-radius: 14px; background: linear-gradient(135deg, #0f766e 0%, #0ea5a4 100%); color: #ffffff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 10px 22px rgba(15, 118, 110, 0.22);"
                        >
                            {{ $this->getSubmitButtonLabel() }}
                        </button>
                    </div>
            </section>
        </form>
    </div>
</x-filament-panels::page>
