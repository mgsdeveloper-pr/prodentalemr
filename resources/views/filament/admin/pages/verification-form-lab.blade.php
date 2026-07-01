<x-filament-panels::page>
    @php
        $stats = $this->getQuestionStats();
        $heroRightContent = '
            <div style="display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                <a href="' . e($this->getQuestionBuilderUrl()) . '" style="display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 18px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800; text-decoration: none;">
                    Create question
                </a>
                <a href="' . e($this->getQuestionManagerUrl()) . '" style="display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 18px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 800; text-decoration: none;">
                    Question management
                </a>
                <a href="' . e($this->getQuestionArrangementUrl()) . '" style="display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 18px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 800; text-decoration: none;">
                    Arrange sections
                </a>
            </div>
        ';
    @endphp

    <style>
        .pd-form-lab-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(340px, 0.85fr);
            gap: 20px;
            align-items: start;
        }

        .pd-form-lab-card {
            border: 1px solid #dbe4ee;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .pd-form-lab-track-grid,
        .pd-form-lab-stack-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .pd-form-lab-phase-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(340px, 0.8fr);
            gap: 20px;
            align-items: start;
        }

        .pd-form-lab-chip-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .pd-form-lab-preview-shell {
            border: 1px solid #dbe4ee;
            border-radius: 28px;
            background: linear-gradient(180deg, #f8fbfa 0%, #f2f7f5 100%);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .pd-form-lab-preview-body {
            padding: 18px;
        }

        .pd-form-lab-section-tree {
            display: grid;
            gap: 14px;
        }

        .pd-form-lab-section-node {
            border: 1px solid #dbe4ee;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .pd-form-lab-arrangement-list {
            display: grid;
            gap: 12px;
        }

        .pd-form-lab-arrangement-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 16px 18px;
            border: 1px solid #dbe4ee;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            cursor: grab;
        }

        .pd-form-lab-arrangement-card.is-dragging {
            opacity: 0.6;
            transform: scale(0.995);
        }

        .pd-form-lab-arrangement-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            border: 1px solid #dbe4ee;
            background: #f8fafc;
            color: #64748b;
            font-size: 18px;
            font-weight: 800;
        }

        @media (max-width: 1080px) {
            .pd-form-lab-grid,
            .pd-form-lab-phase-grid,
            .pd-form-lab-track-grid,
            .pd-form-lab-stack-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .pd-form-lab-chip-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 22px;">
        @include('filament.shared.partials.page-hero', [
            'eyebrow' => 'Form Lab',
            'title' => 'Verification Builder Trial',
            'description' => 'This is the safe sandbox for the new verification builder. We can trial the modern stack, builder flow, and Template 2 preview here before touching the live verification form.',
            'scopeLabel' => 'Current direction',
            'scopeValue' => 'Phase 3 arrangement studio + live preview',
            'rightContent' => $heroRightContent,
        ])

        <div class="pd-form-lab-grid">
            <section class="pd-form-lab-card">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Trial roadmap</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">Build the new form in controlled phases</h3>
                    <div style="margin-top: 8px; font-size: 14px; line-height: 1.7; color: #64748b;">We are starting with the shell and live preview first so the architecture feels right before we add drag-and-drop and deeper logic.</div>
                </div>
                <div style="padding: 20px 22px;">
                    <div class="pd-form-lab-track-grid">
                        @foreach ($this->getBuilderTracks() as $track)
                            <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #f8fafc; padding: 18px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                    <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">{{ $track['eyebrow'] }}</span>
                                    <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #ecfdf5; color: #047857; font-size: 11px; font-weight: 800;">{{ $track['status'] }}</span>
                                </div>
                                <h4 style="margin: 14px 0 0; font-size: 18px; font-weight: 800; color: #0f172a;">{{ $track['title'] }}</h4>
                                <p style="margin: 8px 0 0; font-size: 13px; line-height: 1.7; color: #64748b;">{{ $track['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="pd-form-lab-card">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Modern stack</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">What we are trialing here</h3>
                </div>
                <div style="padding: 20px 22px;">
                    <div class="pd-form-lab-stack-grid">
                        @foreach ($this->getModernStack() as $tool)
                            <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff; padding: 16px 18px;">
                                <div style="font-size: 15px; font-weight: 800; color: #0f172a;">{{ $tool['title'] }}</div>
                                <p style="margin: 8px 0 0; font-size: 13px; line-height: 1.7; color: #64748b;">{{ $tool['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <div class="pd-form-lab-phase-grid">
            <section class="pd-form-lab-card">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Phase 2 logic cockpit</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">Select the template area and confirm how it should behave</h3>
                    <div style="margin-top: 8px; font-size: 14px; line-height: 1.7; color: #64748b;">This is where we validate section logic before drag-and-drop: answer capture, conditional behavior, and what already exists for the selected clinic.</div>
                </div>
                <div style="padding: 20px 22px; display: grid; gap: 18px;">
                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
                        <label style="display: grid; gap: 8px;">
                            <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Template</span>
                            <select wire:model.live="labTemplateKey" style="min-height: 48px; border: 1px solid #d6dde8; border-radius: 14px; background: #ffffff; padding: 0 14px; color: #0f172a; font-size: 14px;">
                                @foreach ($this->getTemplateOptions() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label style="display: grid; gap: 8px;">
                            <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Section</span>
                            <select wire:model.live="labSectionKey" style="min-height: 48px; border: 1px solid #d6dde8; border-radius: 14px; background: #ffffff; padding: 0 14px; color: #0f172a; font-size: 14px;">
                                @foreach ($this->getSectionOptions() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label style="display: grid; gap: 8px;">
                            <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Sub-section</span>
                            <select wire:model.live="labSubSectionKey" style="min-height: 48px; border: 1px solid #d6dde8; border-radius: 14px; background: #ffffff; padding: 0 14px; color: #0f172a; font-size: 14px;" {{ empty($this->getSubSectionOptions()) ? 'disabled' : '' }}>
                                <option value="">No sub-section</option>
                                @foreach ($this->getSubSectionOptions() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="pd-form-lab-chip-grid">
                        <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #f8fafc; padding: 16px 18px;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Selected clinic</div>
                            <div style="margin-top: 8px; font-size: 18px; font-weight: 800; color: #0f172a;">{{ $this->getSelectedClinicName() }}</div>
                        </article>
                        <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #f8fafc; padding: 16px 18px;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Current scope</div>
                            <div style="margin-top: 8px; font-size: 18px; font-weight: 800; color: #0f172a;">{{ $this->getCurrentSectionLabel() }}</div>
                            <div style="margin-top: 4px; font-size: 13px; color: #64748b;">{{ $this->getCurrentSubSectionLabel() }}</div>
                        </article>
                    </div>

                    <div class="pd-form-lab-chip-grid">
                        <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff; padding: 16px 18px;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Existing questions</div>
                            <div style="margin-top: 8px; font-size: 30px; font-weight: 900; color: #0f172a;">{{ $stats['total'] }}</div>
                            <div style="margin-top: 6px; font-size: 13px; color: #64748b;">Active questions already configured in this selected section scope.</div>
                        </article>
                        <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff; padding: 16px 18px;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Logic summary</div>
                            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                                <span style="display: inline-flex; align-items: center; padding: 7px 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 800;">{{ $stats['conditional'] }} conditional</span>
                                <span style="display: inline-flex; align-items: center; padding: 7px 10px; border-radius: 999px; background: #ecfdf5; color: #047857; font-size: 12px; font-weight: 800;">{{ $stats['notes'] }} notes</span>
                                <span style="display: inline-flex; align-items: center; padding: 7px 10px; border-radius: 999px; background: #fff7ed; color: #c2410c; font-size: 12px; font-weight: 800;">{{ $stats['multi'] }} multi / row</span>
                            </div>
                        </article>
                    </div>

                    <div style="display: grid; gap: 12px;">
                        @foreach ($this->getSelectedSectionBehaviors() as $behavior)
                            <article style="border: 1px solid #e2e8f0; border-radius: 18px; background: #ffffff; padding: 16px 18px;">
                                <div style="font-size: 15px; font-weight: 800; color: #0f172a;">{{ $behavior['title'] }}</div>
                                <div style="margin-top: 6px; font-size: 13px; line-height: 1.7; color: #64748b;">{{ $behavior['description'] }}</div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="pd-form-lab-card">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Builder guidance</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">Answer patterns and preview jumps</h3>
                </div>
                <div style="padding: 20px 22px; display: grid; gap: 16px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        @foreach ($this->getPreviewSections() as $previewSection)
                            <a href="{{ $previewSection['anchor'] }}" style="display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 14px; border-radius: 999px; border: 1px solid {{ $previewSection['anchor'] === $this->getPreviewAnchor() ? '#f59e0b' : '#dbe4ee' }}; background: {{ $previewSection['anchor'] === $this->getPreviewAnchor() ? '#fff7ed' : '#ffffff' }}; color: {{ $previewSection['anchor'] === $this->getPreviewAnchor() ? '#b45309' : '#334155' }}; font-size: 12px; font-weight: 800; text-decoration: none;">
                                {{ $previewSection['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div style="padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Preview target</div>
                        <div style="margin-top: 8px; font-size: 14px; line-height: 1.7; color: #0f172a;">The preview jump for this selection points to <strong>{{ $this->getPreviewAnchor() }}</strong>, so we can review the exact section behavior faster.</div>
                    </div>

                    <div style="display: grid; gap: 12px;">
                        @foreach ($this->getAnswerPatternLibrary() as $pattern)
                            <article style="border: 1px solid #e2e8f0; border-radius: 18px; background: #ffffff; padding: 16px 18px;">
                                <div style="font-size: 15px; font-weight: 800; color: #0f172a;">{{ $pattern['title'] }}</div>
                                <div style="margin-top: 6px; font-size: 13px; line-height: 1.7; color: #64748b;">{{ $pattern['description'] }}</div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <div class="pd-form-lab-phase-grid">
            <section class="pd-form-lab-card">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Phase 4 section builder</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">Create template sections and sub-sections from the same lab</h3>
                    <div style="margin-top: 8px; font-size: 14px; line-height: 1.7; color: #64748b;">This uses the real clinic template section table, so anything created here becomes available immediately in Question Management and the Template 2 workflow.</div>
                </div>
                <div style="padding: 20px 22px; display: grid; gap: 18px;">
                    @if (! $this->canManageLabSections())
                        <div style="border: 1px dashed #cbd5e1; border-radius: 22px; background: #f8fafc; padding: 24px; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Your current role can use Form Lab preview and arrangement, but section creation is permission-controlled.
                        </div>
                    @else
                        <div class="pd-form-lab-chip-grid">
                            <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff; padding: 18px;">
                                <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Add section</div>
                                <div style="margin-top: 10px; font-size: 15px; line-height: 1.7; color: #64748b;">Create a new top-level section for this selected clinic and template.</div>
                                <div style="margin-top: 14px; display: grid; gap: 10px;">
                                    <label style="display: grid; gap: 8px;">
                                        <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Section name</span>
                                        <input wire:model.defer="labNewSectionLabel" type="text" placeholder="Example: Implant Coverage" style="min-height: 48px; border: 1px solid #d6dde8; border-radius: 14px; background: #ffffff; padding: 0 14px; color: #0f172a; font-size: 14px;">
                                    </label>
                                    <button type="button" wire:click="createLabSection" style="display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 18px; border-radius: 14px; border: 1px solid #1d4ed8; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800;">
                                        Create section
                                    </button>
                                </div>
                            </article>

                            <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff; padding: 18px;">
                                <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Add sub-section</div>
                                <div style="margin-top: 10px; font-size: 15px; line-height: 1.7; color: #64748b;">Attach a sub-section under any top-level section in this template.</div>
                                <div style="margin-top: 14px; display: grid; gap: 10px;">
                                    <label style="display: grid; gap: 8px;">
                                        <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Parent section</span>
                                        <select wire:model.live="labParentSectionKey" style="min-height: 48px; border: 1px solid #d6dde8; border-radius: 14px; background: #ffffff; padding: 0 14px; color: #0f172a; font-size: 14px;">
                                            @foreach ($this->getSectionOptions() as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label style="display: grid; gap: 8px;">
                                        <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Sub-section name</span>
                                        <input wire:model.defer="labNewSubSectionLabel" type="text" placeholder="Example: Major Restorative Exceptions" style="min-height: 48px; border: 1px solid #d6dde8; border-radius: 14px; background: #ffffff; padding: 0 14px; color: #0f172a; font-size: 14px;">
                                    </label>
                                    <button type="button" wire:click="createLabSubSection" style="display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 18px; border-radius: 14px; border: 1px solid #1d4ed8; background: #ffffff; color: #1d4ed8; font-size: 13px; font-weight: 800;">
                                        Create sub-section
                                    </button>
                                </div>
                            </article>
                        </div>
                    @endif
                </div>
            </section>

            <section class="pd-form-lab-card">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Section map</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">See the full Template 2 structure before placing questions</h3>
                </div>
                <div style="padding: 20px 22px;">
                    <div class="pd-form-lab-section-tree">
                        @foreach ($this->getTemplateSectionTree() as $section)
                            <article class="pd-form-lab-section-node">
                                <div style="padding: 16px 18px; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                                    <div>
                                        <div style="font-size: 16px; font-weight: 800; color: #0f172a;">{{ $section['label'] }}</div>
                                        <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 8px;">
                                            <span style="display: inline-flex; align-items: center; padding: 5px 8px; border-radius: 999px; background: {{ $section['is_builtin'] ? '#f8fafc' : '#eff6ff' }}; border: 1px solid {{ $section['is_builtin'] ? '#dbe4ee' : '#bfdbfe' }}; color: {{ $section['is_builtin'] ? '#64748b' : '#1d4ed8' }}; font-size: 11px; font-weight: 800;">
                                                {{ $section['is_builtin'] ? 'Master section' : 'Clinic section' }}
                                            </span>
                                            <span style="display: inline-flex; align-items: center; padding: 5px 8px; border-radius: 999px; background: #ecfdf5; border: 1px solid #bbf7d0; color: #047857; font-size: 11px; font-weight: 800;">
                                                {{ $section['question_count'] }} questions
                                            </span>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="focusLabScope('{{ $section['key'] }}')" style="display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 0 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 12px; font-weight: 800;">
                                        Work in this section
                                    </button>
                                </div>
                                @if (! empty($section['children']))
                                    <div style="padding: 0 18px 18px;">
                                        <div style="display: grid; gap: 10px;">
                                            @foreach ($section['children'] as $child)
                                                <div style="border: 1px solid #e2e8f0; border-radius: 16px; background: #f8fafc; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                                                    <div>
                                                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $child['label'] }}</div>
                                                        <div style="margin-top: 5px; display: flex; flex-wrap: wrap; gap: 8px;">
                                                            <span style="display: inline-flex; align-items: center; padding: 4px 7px; border-radius: 999px; background: {{ $child['is_builtin'] ? '#f8fafc' : '#eff6ff' }}; border: 1px solid {{ $child['is_builtin'] ? '#dbe4ee' : '#bfdbfe' }}; color: {{ $child['is_builtin'] ? '#64748b' : '#1d4ed8' }}; font-size: 10px; font-weight: 800;">
                                                                {{ $child['is_builtin'] ? 'Master sub-section' : 'Clinic sub-section' }}
                                                            </span>
                                                            <span style="display: inline-flex; align-items: center; padding: 4px 7px; border-radius: 999px; background: #ecfdf5; border: 1px solid #bbf7d0; color: #047857; font-size: 10px; font-weight: 800;">
                                                                {{ $child['question_count'] }} questions
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <button type="button" wire:click="focusLabScope('{{ $section['key'] }}', '{{ $child['key'] }}')" style="display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 12px; border-radius: 12px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 12px; font-weight: 800;">
                                                        Open sub-section
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <section
            class="pd-form-lab-card"
            wire:key="form-lab-arrangement-{{ $this->labTemplateKey }}-{{ $this->labSectionKey }}-{{ $this->labSubSectionKey ?: 'none' }}-{{ count($this->getArrangementQuestionCards()) }}"
            x-data="{
                items: @js($this->getArrangementQuestionCards()),
                originalOrder: @js(collect($this->getArrangementQuestionCards())->pluck('id')->map(fn ($id) => (int) $id)->values()->all()),
                draggingId: null,
                dragStart(id) {
                    this.draggingId = id;
                },
                dragEnd() {
                    this.draggingId = null;
                },
                moveBefore(targetId) {
                    if (this.draggingId === null || this.draggingId === targetId) return;
                    const fromIndex = this.items.findIndex(item => item.id === this.draggingId);
                    if (fromIndex === -1) return;
                    const [moved] = this.items.splice(fromIndex, 1);
                    const newTargetIndex = this.items.findIndex(item => item.id === targetId);
                    if (newTargetIndex === -1) return;
                    this.items.splice(newTargetIndex, 0, moved);
                },
                hasChanged() {
                    return JSON.stringify(this.items.map(item => item.id)) !== JSON.stringify(this.originalOrder);
                },
                async saveOrder() {
                    const saved = await $wire.saveArrangementOrder(this.items.map(item => item.id));
                    if (saved) {
                        this.originalOrder = this.items.map(item => item.id);
                    }
                }
            }"
        >
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Phase 3 arrangement studio</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">Drag the question order before we take this into the live builder</h3>
                    <div style="margin-top: 8px; font-size: 14px; line-height: 1.7; color: #64748b;">{{ $this->getArrangementSummary() }}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                    <span style="display: inline-flex; align-items: center; padding: 8px 12px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 800;">
                        {{ count($this->getArrangementQuestionCards()) }} draggable items
                    </span>
                    <button
                        type="button"
                        x-on:click="saveOrder()"
                        x-bind:disabled="!hasChanged()"
                        x-bind:style="!hasChanged() ? 'opacity: 0.45; cursor: not-allowed;' : ''"
                        style="display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 18px; border-radius: 14px; border: 1px solid #047857; background: #059669; color: #ffffff; font-size: 13px; font-weight: 800;"
                    >
                        Save new order
                    </button>
                </div>
            </div>

            <div style="padding: 20px 22px; display: grid; gap: 16px;">
                <div class="pd-form-lab-chip-grid">
                    <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #f8fafc; padding: 16px 18px;">
                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Template scope</div>
                        <div style="margin-top: 8px; font-size: 18px; font-weight: 800; color: #0f172a;">{{ $this->getCurrentSectionLabel() }}</div>
                        <div style="margin-top: 4px; font-size: 13px; color: #64748b;">{{ $this->getCurrentSubSectionLabel() }}</div>
                    </article>
                    <article style="border: 1px solid #e2e8f0; border-radius: 20px; background: #f8fafc; padding: 16px 18px;">
                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">How it works</div>
                        <div style="margin-top: 8px; font-size: 14px; line-height: 1.7; color: #475569;">Drag a row by the handle, drop it above another row, then save. The lab writes back to the same clinic question order used by the real verification template.</div>
                    </article>
                </div>

                <template x-if="items.length === 0">
                    <div style="border: 1px dashed #cbd5e1; border-radius: 22px; background: #f8fafc; padding: 26px; text-align: center; font-size: 14px; line-height: 1.7; color: #64748b;">
                        No active questions are available in this selected scope yet. Create questions first, then come back here to arrange them.
                    </div>
                </template>

                <div class="pd-form-lab-arrangement-list" x-show="items.length > 0">
                    <template x-for="item in items" :key="item.id">
                        <article
                            class="pd-form-lab-arrangement-card"
                            x-bind:class="{ 'is-dragging': draggingId === item.id }"
                            draggable="true"
                            x-on:dragstart="dragStart(item.id)"
                            x-on:dragend="dragEnd()"
                            x-on:dragover.prevent
                            x-on:drop.prevent="moveBefore(item.id)"
                        >
                            <span class="pd-form-lab-arrangement-handle">::</span>
                            <div style="min-width: 0;">
                                <div style="font-size: 15px; line-height: 1.5; font-weight: 800; color: #0f172a;" x-text="item.prompt"></div>
                                <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 8px;">
                                    <span style="display: inline-flex; align-items: center; padding: 5px 8px; border-radius: 999px; background: #f8fafc; border: 1px solid #dbe4ee; color: #64748b; font-size: 11px; font-weight: 800;" x-text="item.section_label"></span>
                                    <span style="display: inline-flex; align-items: center; padding: 5px 8px; border-radius: 999px; background: #ecfdf5; border: 1px solid #bbf7d0; color: #047857; font-size: 11px; font-weight: 800;" x-text="item.input_type"></span>
                                </div>
                            </div>
                            <div style="display: grid; justify-items: end; gap: 6px;">
                                <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #94a3b8;">Current order</div>
                                <div style="font-size: 14px; font-weight: 800; color: #0f172a;" x-text="'#' + item.sort_order"></div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </section>

        <section class="pd-form-lab-card">
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Live preview</div>
                    <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 800; color: #0f172a;">Template 2 preview inside the new lab shell</h3>
                    <div style="margin-top: 8px; font-size: 14px; line-height: 1.7; color: #64748b;">This stays isolated from the live verification workflow so we can keep testing structure, spacing, and builder ideas safely.</div>
                </div>
                <span style="display: inline-flex; align-items: center; padding: 9px 14px; border-radius: 999px; background: #ecfdf5; color: #047857; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;">
                    Template 2 sandbox
                </span>
            </div>
            <div class="pd-form-lab-preview-body">
                <div class="pd-form-lab-preview-shell">
                    @include('filament.admin.pages.verification-form-lab-ultraeligex-modern')
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
