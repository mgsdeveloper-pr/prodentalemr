<x-filament-panels::page>
    @php($credentials = $this->getPortalCredentials())
    @php($summary = $this->getCredentialSummary())

    <div
        class="pd-credential-workspace"
        wire:poll.30s="clearExpiredPortalCredentialValues"
        x-data="{
            copyProtectedValue(value) {
                if (!value) return;
                const fallback = () => {
                    const field = document.createElement('textarea');
                    field.value = value;
                    field.setAttribute('readonly', '');
                    field.style.position = 'fixed';
                    field.style.opacity = '0';
                    document.body.appendChild(field);
                    field.select();
                    document.execCommand('copy');
                    field.remove();
                };
                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(value).catch(fallback);
                    return;
                }
                fallback();
            }
        }"
    >
        <header class="pd-credential-header">
            <div>
                <h1>Portal Credentials</h1>
                <p>Use the payer portal access assigned to the selected clinic.</p>
                <nav aria-label="Breadcrumb">
                    <span>Verification</span>
                    <x-heroicon-o-chevron-right />
                    <strong>Portal Credentials</strong>
                </nav>
            </div>
            <div class="pd-credential-header-actions">
                <div class="pd-credential-scope">
                    <span>Clinic</span>
                    <strong>{{ $this->getSelectedClinicName() ?: 'Select clinic scope' }}</strong>
                </div>
                @if ($this->canCreatePortalCredentials())
                    <a class="pd-credential-add" href="{{ $this->createCredentialUrl() }}" wire:navigate>
                        <x-heroicon-o-plus />
                        <span>Add Credential</span>
                    </a>
                @endif
            </div>
        </header>

        <section class="pd-credential-summary" aria-label="Credential summary">
            <div><span>Available portals</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>Active</span><strong>{{ number_format($summary['active']) }}</strong></div>
            <div><span>MFA enabled</span><strong>{{ number_format($summary['mfa']) }}</strong></div>
            <p>Credentials are maintained per clinic. Secret access is recorded for security review.</p>
        </section>

        <section class="pd-credential-table-card">
            <div class="pd-credential-toolbar">
                <label class="pd-credential-search">
                    <x-heroicon-o-magnifying-glass />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search portal, category, or account">
                </label>
                <span>{{ $credentials->count() }} result{{ $credentials->count() === 1 ? '' : 's' }}</span>
            </div>

            <div class="pd-credential-table-wrap">
                <table class="pd-credential-table">
                    <thead>
                        <tr>
                            <th>Portal</th>
                            <th>Portal Access</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>MFA</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($credentials as $credential)
                            <tr wire:key="portal-credential-{{ $credential->getKey() }}">
                                <td>
                                    <div class="pd-credential-primary">
                                        <strong>{{ $credential->portal_name }}</strong>
                                        <span>{{ \App\Models\PortalCredential::CATEGORY_OPTIONS[$credential->portal_category ?: 'other'] ?? 'Other' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if (filled($credential->login_url))
                                        <a class="pd-credential-link" href="{{ $credential->login_url }}" target="_blank" rel="noopener noreferrer">
                                            <x-heroicon-o-arrow-top-right-on-square /><span>Open portal</span>
                                        </a>
                                    @else
                                        <span class="pd-muted">Not provided</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="pd-secret-field">
                                        <code id="portal-username-{{ $credential->getKey() }}">{{ $this->portalCredentialDisplayValue('portal-username-'.$credential->getKey(), \App\Models\PortalCredential::maskSecret($credential->username)) }}</code>
                                        @if (filled($credential->username) && $this->canUpdatePasswords())
                                            <button type="button" wire:click="revealCredentialSecret({{ $credential->getKey() }}, 'username')" wire:loading.attr="disabled" wire:target="revealCredentialSecret({{ $credential->getKey() }}, 'username')" title="Reveal username" aria-label="Reveal username for {{ $credential->portal_name }}"><x-heroicon-o-eye /></button>
                                            <button type="button" x-on:click="$wire.copyCredentialSecret({{ $credential->getKey() }}, 'username').then(value => copyProtectedValue(value))" wire:loading.attr="disabled" wire:target="copyCredentialSecret({{ $credential->getKey() }}, 'username')" title="Copy username" aria-label="Copy username for {{ $credential->portal_name }}"><x-heroicon-o-clipboard /></button>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="pd-secret-field">
                                        <code id="portal-password-{{ $credential->getKey() }}">{{ $this->portalCredentialDisplayValue('portal-password-'.$credential->getKey(), \App\Models\PortalCredential::maskSecret($credential->password)) }}</code>
                                        @if (filled($credential->password) && $this->canUpdatePasswords())
                                            <button type="button" wire:click="revealCredentialSecret({{ $credential->getKey() }}, 'password')" wire:loading.attr="disabled" wire:target="revealCredentialSecret({{ $credential->getKey() }}, 'password')" title="Reveal password" aria-label="Reveal password for {{ $credential->portal_name }}"><x-heroicon-o-eye /></button>
                                            <button type="button" x-on:click="$wire.copyCredentialSecret({{ $credential->getKey() }}, 'password').then(value => copyProtectedValue(value))" wire:loading.attr="disabled" wire:target="copyCredentialSecret({{ $credential->getKey() }}, 'password')" title="Copy password" aria-label="Copy password for {{ $credential->portal_name }}"><x-heroicon-o-clipboard /></button>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="pd-mfa-summary">
                                        <span class="pd-credential-pill {{ $credential->mfa_required ? 'is-info' : '' }}">{{ $credential->mfa_required ? (\App\Models\PortalCredential::MFA_METHOD_OPTIONS[$credential->mfa_method ?: 'none'] ?? 'Required') : 'Not required' }}</span>
                                        @if ($credential->mfa_required && $credential->mfa_method === 'security_question')
                                            <span>{{ $credential->security_questions_count }} question{{ $credential->security_questions_count === 1 ? '' : 's' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="pd-credential-pill {{ $credential->is_active ? 'is-active' : '' }}">{{ $credential->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="pd-date">{{ optional($credential->updated_at)->format('M d, Y') ?: '-' }}</td>
                                <td>
                                    @if ($this->canUpdatePasswords())
                                        <div class="pd-credential-actions">
                                            <a href="{{ $this->editCredentialUrl($credential) }}" wire:navigate title="Edit credential"><x-heroicon-o-pencil-square /><span>Edit</span></a>
                                            <button type="button" wire:click="openPasswordEditor({{ $credential->getKey() }})" title="Change password"><x-heroicon-o-key /><span>Password</span></button>
                                            @if ($credential->security_questions_count > 0)
                                                <button type="button" wire:click="openSecurityQuestions({{ $credential->getKey() }})" wire:loading.attr="disabled" wire:target="openSecurityQuestions({{ $credential->getKey() }})" title="View security questions and answers"><x-heroicon-o-shield-check /><span>Security Q&amp;A</span></button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="pd-credential-empty"><x-heroicon-o-key /><strong>No portal credentials available</strong><span>No visible credentials have been assigned to this clinic yet.</span>@if ($this->canCreatePortalCredentials())<a class="pd-credential-add" href="{{ $this->createCredentialUrl() }}" wire:navigate><x-heroicon-o-plus /><span>Add Credential</span></a>@endif</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    @if ($this->passwordModalOpen)
        <div class="pd-password-backdrop" wire:keydown.escape.window="closePasswordEditor">
            <section class="pd-password-modal" role="dialog" aria-modal="true" aria-labelledby="password-modal-title">
                <header>
                    <div><span>Security update</span><h2 id="password-modal-title">Change Portal Password</h2><p>{{ $this->editingCredentialName }}</p></div>
                    <button type="button" wire:click="closePasswordEditor" aria-label="Close password dialog"><x-heroicon-o-x-mark /></button>
                </header>
                <div class="pd-password-body">
                    <div class="pd-password-context">
                        <div><span>Username</span><strong>{{ $this->editingCredentialUsername ?: '-' }}</strong></div>
                        <div><span>Portal</span>@if (filled($this->editingCredentialLink))<a href="{{ $this->editingCredentialLink }}" target="_blank" rel="noopener noreferrer">Open portal</a>@else<strong>Not provided</strong>@endif</div>
                    </div>
                    <label><span>New password</span><input type="password" wire:model.defer="newPassword" autocomplete="new-password">@error('newPassword')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Confirm password</span><input type="password" wire:model.defer="newPasswordConfirmation" autocomplete="new-password">@error('newPasswordConfirmation')<small>{{ $message }}</small>@enderror</label>
                    <p class="pd-password-note">The previous password remains in protected history. Only the five latest password changes are retained.</p>
                </div>
                <footer>
                    <button type="button" class="is-neutral" wire:click="closePasswordEditor">Cancel</button>
                    <button type="button" class="is-primary" wire:click="updateCredentialPassword" wire:loading.attr="disabled" wire:target="updateCredentialPassword"><span wire:loading.remove wire:target="updateCredentialPassword">Save password</span><span wire:loading wire:target="updateCredentialPassword">Saving...</span></button>
                </footer>
            </section>
        </div>
    @endif

    @if ($this->securityQuestionsModalOpen)
        <div class="pd-password-backdrop" wire:keydown.escape.window="closeSecurityQuestions">
            <section class="pd-security-modal" role="dialog" aria-modal="true" aria-labelledby="security-questions-modal-title">
                <header>
                    <div>
                        <span>Protected portal access</span>
                        <h2 id="security-questions-modal-title">Security Questions &amp; Answers</h2>
                        <p>{{ $this->securityQuestionsCredentialName }}</p>
                    </div>
                    <button type="button" wire:click="closeSecurityQuestions" aria-label="Close security questions dialog"><x-heroicon-o-x-mark /></button>
                </header>
                <div class="pd-security-body">
                    <p class="pd-security-intro"><x-heroicon-o-shield-check /> Answers remain masked until explicitly revealed. Reveal and copy actions are recorded in the security audit.</p>
                    <div class="pd-security-list">
                        @forelse ($this->securityQuestionRows as $index => $question)
                            <article wire:key="portal-security-question-{{ $question['id'] }}">
                                <div class="pd-security-question">
                                    <span>Question {{ $index + 1 }}</span>
                                    <strong>{{ $question['question'] }}</strong>
                                    <em class="{{ $question['is_required'] ? 'is-required' : '' }}">{{ $question['is_required'] ? 'Required' : 'Optional' }}</em>
                                </div>
                                <div class="pd-security-answer">
                                    <span>Protected answer</span>
                                    <div class="pd-secret-field">
                                            <code id="portal-security-answer-{{ $question['id'] }}">{{ $this->portalCredentialDisplayValue('portal-security-answer-'.$question['id'], $question['masked_answer']) }}</code>
                                        <button type="button" wire:click="revealSecurityQuestionAnswer({{ $this->securityQuestionsCredentialId }}, {{ $question['id'] }})" wire:loading.attr="disabled" wire:target="revealSecurityQuestionAnswer({{ $this->securityQuestionsCredentialId }}, {{ $question['id'] }})" title="Reveal security answer" aria-label="Reveal answer for question {{ $index + 1 }}"><x-heroicon-o-eye /></button>
                                        <button type="button" x-on:click="$wire.copySecurityQuestionAnswer({{ $this->securityQuestionsCredentialId }}, {{ $question['id'] }}).then(value => copyProtectedValue(value))" wire:loading.attr="disabled" wire:target="copySecurityQuestionAnswer({{ $this->securityQuestionsCredentialId }}, {{ $question['id'] }})" title="Copy security answer" aria-label="Copy answer for question {{ $index + 1 }}"><x-heroicon-o-clipboard /></button>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="pd-security-empty">No security questions are stored for this credential.</div>
                        @endforelse
                    </div>
                </div>
                <footer><button type="button" class="is-primary" wire:click="closeSecurityQuestions">Done</button></footer>
            </section>
        </div>
    @endif

    </div>

    <style>
        .pd-credential-workspace{display:flex;flex-direction:column;gap:20px}.pd-credential-header{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:20px 28px;margin:-24px -24px 0;border-bottom:1px solid #dbe4ee;background:#fff}.pd-credential-header h1{margin:0;color:#07152f;font-size:28px;line-height:1.2;font-weight:800}.pd-credential-header p{margin:7px 0 10px;color:#64748b;font-size:14px}.pd-credential-header nav{display:flex;align-items:center;gap:7px;color:#64748b;font-size:12px}.pd-credential-header nav svg{width:14px;height:14px}.pd-credential-header nav strong{color:#0f172a}.pd-credential-scope{min-width:220px;padding:10px 14px;border:1px solid #dbe4ee;border-radius:8px;background:#f8fafc}.pd-credential-scope span{display:block;margin-bottom:3px;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase}.pd-credential-scope strong{display:block;overflow:hidden;color:#0f172a;font-size:13px;text-overflow:ellipsis;white-space:nowrap}.pd-credential-summary{display:grid;grid-template-columns:repeat(3,150px) 1fr;align-items:stretch;border:1px solid #dbe4ee;border-radius:8px;background:#fff;overflow:hidden}.pd-credential-summary>div{padding:14px 18px;border-right:1px solid #edf2f7}.pd-credential-summary span{display:block;color:#64748b;font-size:11px;font-weight:700}.pd-credential-summary strong{display:block;margin-top:4px;color:#07152f;font-size:21px}.pd-credential-summary p{align-self:center;margin:0;padding:14px 18px;color:#64748b;font-size:12px;text-align:right}.pd-credential-table-card{border:1px solid #dbe4ee;border-radius:8px;background:#fff;overflow:hidden;box-shadow:0 3px 10px rgba(15,23,42,.04)}.pd-credential-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;font-weight:700}.pd-credential-search{position:relative;display:block;width:min(420px,100%)}.pd-credential-search svg{position:absolute;left:12px;top:50%;width:17px;height:17px;color:#94a3b8;transform:translateY(-50%)}.pd-credential-search input{width:100%;height:40px;padding:0 13px 0 39px;border:1px solid #cfd9e6;border-radius:7px;background:#fff;color:#0f172a;font-size:13px;outline:none}.pd-credential-search input:focus{border-color:#0f8a83;box-shadow:0 0 0 3px rgba(15,138,131,.1)}.pd-credential-table-wrap{overflow-x:auto}.pd-credential-table{width:100%;min-width:1320px;border-collapse:collapse}.pd-credential-table th{padding:12px 14px;border-bottom:1px solid #dbe4ee;background:#f8fafc;color:#334155;font-size:11px;font-weight:800;text-align:left;white-space:nowrap}.pd-credential-table td{padding:14px;border-bottom:1px solid #edf2f7;color:#334155;font-size:12px;vertical-align:middle}.pd-credential-table tbody tr:last-child td{border-bottom:0}.pd-credential-table tbody tr:hover{background:#fbfefe}.pd-credential-primary strong{display:block;color:#07152f;font-size:13px}.pd-credential-primary span{display:block;margin-top:4px;color:#64748b;font-size:11px}.pd-credential-link{display:inline-flex;align-items:center;gap:6px;color:#0f766e;font-weight:700;text-decoration:none}.pd-credential-link svg{width:15px;height:15px}.pd-muted{color:#94a3b8}.pd-secret-field{display:inline-flex;align-items:center;gap:5px;white-space:nowrap}.pd-secret-field code{min-width:82px;color:#334155;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px}.pd-secret-field button{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border:1px solid #dbe4ee;border-radius:6px;background:#fff;color:#475569;cursor:pointer}.pd-secret-field button:hover{border-color:#99d5d1;color:#0f766e}.pd-secret-field button:disabled{cursor:wait;opacity:.55}.pd-secret-field button svg{width:15px;height:15px}.pd-mfa-summary{display:flex;align-items:flex-start;flex-direction:column;gap:5px}.pd-mfa-summary>span:last-child:not(:first-child){color:#64748b;font-size:10px;font-weight:700}.pd-credential-pill{display:inline-flex;align-items:center;padding:4px 8px;border:1px solid #dbe4ee;border-radius:999px;background:#f8fafc;color:#64748b;font-size:10px;font-weight:700;white-space:nowrap}.pd-credential-pill.is-info{border-color:#bfdbfe;background:#eff6ff;color:#1d4ed8}.pd-credential-pill.is-active{border-color:#a7e4cb;background:#effaf5;color:#067647}.pd-date{color:#64748b!important;white-space:nowrap}.pd-credential-actions{display:flex;align-items:center;justify-content:flex-end;gap:6px}.pd-credential-actions a,.pd-credential-actions button{display:inline-flex;align-items:center;gap:5px;min-height:32px;padding:0 9px;border:1px solid #dbe4ee;border-radius:6px;background:#fff;color:#334155;font-size:11px;font-weight:700;text-decoration:none;cursor:pointer;white-space:nowrap}.pd-credential-actions a:hover,.pd-credential-actions button:hover{border-color:#99d5d1;color:#0f766e}.pd-credential-actions svg{width:14px;height:14px}.pd-credential-empty{display:flex;flex-direction:column;align-items:center;gap:8px;padding:54px 24px;color:#64748b;text-align:center}.pd-credential-empty svg{width:28px;height:28px}.pd-credential-empty strong{color:#0f172a;font-size:16px}.pd-password-backdrop{position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:24px;background:rgba(15,23,42,.48)}.pd-password-modal,.pd-security-modal{width:min(520px,100%);max-height:min(720px,calc(100vh - 48px));border:1px solid #dbe4ee;border-radius:8px;background:#fff;overflow:hidden;box-shadow:0 28px 70px rgba(15,23,42,.25)}.pd-security-modal{width:min(720px,100%)}.pd-password-modal>header,.pd-security-modal>header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px;border-bottom:1px solid #edf2f7}.pd-password-modal>header span,.pd-security-modal>header span{color:#0f766e;font-size:10px;font-weight:800;text-transform:uppercase}.pd-password-modal h2,.pd-security-modal h2{margin:5px 0 0;color:#07152f;font-size:21px}.pd-password-modal header p,.pd-security-modal header p{margin:4px 0 0;color:#64748b;font-size:12px}.pd-password-modal header button,.pd-security-modal header button{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #dbe4ee;border-radius:6px;background:#fff;color:#475569;cursor:pointer}.pd-password-modal header button svg,.pd-security-modal header button svg{width:18px;height:18px}.pd-password-body{display:flex;flex-direction:column;gap:15px;padding:20px 22px}.pd-password-context{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.pd-password-context>div{padding:10px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc}.pd-password-context span,.pd-password-body label>span{display:block;margin-bottom:5px;color:#64748b;font-size:10px;font-weight:800;text-transform:uppercase}.pd-password-context strong,.pd-password-context a{color:#0f172a;font-size:12px;font-weight:700}.pd-password-body input{width:100%;height:42px;padding:0 12px;border:1px solid #cfd9e6;border-radius:6px;color:#0f172a;font-size:13px;outline:none}.pd-password-body input:focus{border-color:#0f8a83;box-shadow:0 0 0 3px rgba(15,138,131,.1)}.pd-password-body small{display:block;margin-top:5px;color:#dc2626;font-size:11px}.pd-password-note{margin:0;color:#64748b;font-size:11px;line-height:1.6}.pd-security-body{max-height:520px;padding:18px 22px;overflow-y:auto}.pd-security-intro{display:flex;align-items:flex-start;gap:8px;margin:0 0 14px;padding:10px 12px;border:1px solid #cde8e4;border-radius:6px;background:#f0fdfa;color:#335b59;font-size:11px;line-height:1.5}.pd-security-intro svg{flex:0 0 auto;width:17px;height:17px;color:#0f766e}.pd-security-list{display:flex;flex-direction:column;gap:9px}.pd-security-list article{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:14px;padding:13px;border:1px solid #e2e8f0;border-radius:7px;background:#fff}.pd-security-question{position:relative;padding-right:76px}.pd-security-question>span,.pd-security-answer>span{display:block;margin-bottom:5px;color:#64748b;font-size:9px;font-weight:800;text-transform:uppercase}.pd-security-question strong{display:block;color:#0f172a;font-size:12px;line-height:1.45}.pd-security-question em{position:absolute;right:0;top:0;padding:3px 7px;border:1px solid #dbe4ee;border-radius:999px;color:#64748b;font-size:9px;font-style:normal;font-weight:800}.pd-security-question em.is-required{border-color:#a7e4cb;background:#effaf5;color:#067647}.pd-security-answer .pd-secret-field{display:flex}.pd-security-answer code{flex:1}.pd-security-empty{padding:28px;color:#64748b;text-align:center}.pd-password-modal footer,.pd-security-modal footer{display:flex;justify-content:flex-end;gap:8px;padding:14px 22px;border-top:1px solid #edf2f7}.pd-password-modal footer button,.pd-security-modal footer button{min-height:38px;padding:0 14px;border-radius:6px;font-size:12px;font-weight:800;cursor:pointer}.pd-password-modal footer .is-neutral{border:1px solid #dbe4ee;background:#fff;color:#334155}.pd-password-modal footer .is-primary,.pd-security-modal footer .is-primary{border:1px solid #0f766e;background:#0f766e;color:#fff}@media(max-width:900px){.pd-credential-header{align-items:flex-start;flex-direction:column}.pd-credential-scope{width:100%}.pd-credential-summary{grid-template-columns:repeat(3,1fr)}.pd-credential-summary p{grid-column:1/-1;border-top:1px solid #edf2f7;text-align:left}}@media(max-width:640px){.pd-credential-header{margin-inline:-16px;padding:18px 16px}.pd-credential-summary{grid-template-columns:1fr}.pd-credential-summary>div{border-right:0;border-bottom:1px solid #edf2f7}.pd-credential-summary p{grid-column:auto}.pd-credential-toolbar{align-items:stretch;flex-direction:column}.pd-password-context,.pd-security-list article{grid-template-columns:1fr}.pd-security-question{padding-right:0}.pd-security-question em{position:static;display:inline-flex;margin-top:7px}}
    </style>

    <style>
        .pd-credential-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pd-credential-add {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 0 14px;
            border: 1px solid #0f766e;
            border-radius: 7px;
            background: #0f766e;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .pd-credential-add:hover {
            border-color: #115e59;
            background: #115e59;
            color: #fff;
        }

        .pd-credential-add svg {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 900px) {
            .pd-credential-header-actions {
                width: 100%;
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>

</x-filament-panels::page>
