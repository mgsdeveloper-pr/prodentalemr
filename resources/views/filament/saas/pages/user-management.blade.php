<x-filament-panels::page>
    <div class="saas-users">
        <section class="saas-users__summary" aria-label="User account summary">
            <div><span>Total users</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>Active</span><strong>{{ number_format($summary['active']) }}</strong></div>
            <div><span>Roles in use</span><strong>{{ number_format($summary['roles']) }}</strong></div>
            <div><span>Calling enabled</span><strong>{{ number_format($summary['calling']) }}</strong></div>
        </section>

        <nav class="saas-users__tabs" aria-label="User management sections">
            <span class="is-active" aria-current="page">Users</span>
            @if ($rolesUrl)
                <a href="{{ $rolesUrl }}" wire:navigate>Roles &amp; Permissions</a>
            @endif
            @if ($callingUrl)
                <a href="{{ $callingUrl }}" wire:navigate>Calling Access</a>
            @endif
        </nav>

        <section class="saas-users__workspace" aria-label="Platform users">
            <div class="saas-users__toolbar">
                <label class="saas-users__search">
                    <span class="sr-only">Search platform users</span>
                    <x-heroicon-o-magnifying-glass />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or email" autocomplete="off">
                    @if (filled($search))
                        <button type="button" wire:click="$set('search', '')" aria-label="Clear user search"><x-heroicon-o-x-mark /></button>
                    @endif
                </label>

                <label class="saas-users__filter">
                    <x-heroicon-o-list-bullet />
                    <span class="sr-only">Filter users by status</span>
                    <select wire:model.live="status" aria-label="Filter users by status">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @if ($users->isEmpty())
                <div class="saas-users__empty">
                    <x-heroicon-o-user-group />
                    <strong>No users found</strong>
                    <span>{{ filled($search) || $status !== 'all' ? 'Try a different search or status filter.' : 'Add the first platform user to begin managing access.' }}</span>
                </div>
            @else
                <div class="saas-users__table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Access scope</th>
                                <th>Calling</th>
                                <th>Status</th>
                                <th>Last active</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php($actionUrl = $this->userActionUrl($user))
                                <tr wire:key="platform-user-{{ $user->getKey() }}">
                                    <td>
                                        <div class="saas-users__identity">
                                            <span aria-hidden="true">{{ $this->initials($user) }}</span>
                                            <div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div>
                                        </div>
                                    </td>
                                    <td>{{ $user->getPrimaryRoleLabel() ?? 'Not assigned' }}</td>
                                    <td>{{ $this->accessScope($user) }}</td>
                                    <td><span class="saas-users__calling {{ $user->active_calling_assignments_count > 0 ? 'is-enabled' : '' }}">{{ $user->active_calling_assignments_count > 0 ? 'Enabled' : 'Not enabled' }}</span></td>
                                    <td><span class="saas-users__status is-{{ $this->statusTone($user) }}"><i aria-hidden="true"></i>{{ $this->statusLabel($user) }}</span></td>
                                    <td>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td>
                                        @if ($actionUrl)
                                            <a class="saas-users__icon-button" href="{{ $actionUrl }}" wire:navigate aria-label="{{ $this->canEditUser($user) ? 'Edit' : 'View' }} {{ $user->name }}">
                                                @if ($this->canEditUser($user))
                                                    <x-heroicon-o-pencil-square />
                                                @else
                                                    <x-heroicon-o-eye />
                                                @endif
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="saas-users__pagination">
                        <span>Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }}</span>
                        <div>
                            <button type="button" wire:click="previousPage" @disabled($users->onFirstPage()) aria-label="Previous users page"><x-heroicon-o-chevron-left /></button>
                            <strong>Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</strong>
                            <button type="button" wire:click="nextPage" @disabled(! $users->hasMorePages()) aria-label="Next users page"><x-heroicon-o-chevron-right /></button>
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>

    <style>
        .saas-users{--su-border:#dbe4ee;--su-navy:#0f172a;--su-muted:#64748b;display:flex;min-width:0;max-width:100%;font-size:var(--pwdl-font-size-body,0.875rem);line-height:1.5;flex-direction:column;gap:18px}.saas-users__summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border:1px solid var(--su-border);border-radius:8px;background:#fff;overflow:hidden}.saas-users__summary>div{min-height:78px;padding:14px 17px;border-right:1px solid var(--su-border)}.saas-users__summary>div:last-child{border-right:0}.saas-users__summary span,.saas-users__summary strong{display:block}.saas-users__summary span{color:var(--su-muted);font-size:var(--pwdl-font-size-caption,0.75rem)}.saas-users__summary strong{margin-top:5px;color:var(--su-navy);font-size:21px;font-weight:850}.saas-users__tabs{display:flex;align-items:flex-end;gap:25px;min-height:46px;border-bottom:1px solid var(--su-border);overflow-x:auto}.saas-users__tabs a,.saas-users__tabs span{display:inline-flex;height:46px;align-items:center;border-bottom:2px solid transparent;color:#52637a;font-size:var(--pwdl-font-size-body,0.875rem);font-weight:800;text-decoration:none;white-space:nowrap}.saas-users__tabs a:hover{color:#0f766e}.saas-users__tabs .is-active{border-bottom-color:#0f766e;color:#0f766e}.saas-users__workspace{border:1px solid var(--su-border);border-radius:8px;background:#fff;overflow:hidden}.saas-users__toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 14px;border-bottom:1px solid var(--su-border)}.saas-users__search{display:flex;width:min(390px,100%);height:40px;align-items:center;gap:8px;padding:0 10px;border:1px solid #cfd9e6;border-radius:6px;background:#fff;color:#64748b}.saas-users__search>svg,.saas-users__filter>svg{width:16px;height:16px;flex:0 0 auto}.saas-users__search input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:var(--su-navy);font-size:var(--pwdl-font-size-body,0.875rem)}.saas-users__search button{display:inline-flex;width:28px;height:28px;align-items:center;justify-content:center;border:0;background:transparent;color:#64748b;cursor:pointer}.saas-users__search button svg{width:15px;height:15px}.saas-users__filter{display:flex;height:40px;align-items:center;gap:7px;padding:0 9px;border:1px solid #cfd9e6;border-radius:6px;background:#fff;color:#334155}.saas-users__filter select{min-width:138px;border:0;outline:0;background:#fff;color:#334155;font-size:var(--pwdl-font-size-body,0.875rem);font-weight:700}.saas-users__table-wrap{overflow-x:auto}.saas-users table{width:100%;min-width:920px;border-collapse:collapse}.saas-users th{padding:10px 14px;background:#f8fafc;color:#52637a;font-size:var(--pwdl-font-size-caption,0.75rem);font-weight:800;text-align:left;text-transform:uppercase;white-space:nowrap}.saas-users td{padding:12px 14px;border-top:1px solid #edf2f7;color:#475569;font-size:var(--pwdl-font-size-body,0.875rem);vertical-align:middle}.saas-users tbody tr:hover{background:#fbfefe}.saas-users__identity{display:flex;min-width:190px;align-items:center;gap:10px}.saas-users__identity>span{display:inline-flex;width:34px;height:34px;align-items:center;justify-content:center;flex:0 0 auto;border-radius:999px;background:#f0fdfa;color:#0f766e;font-size:var(--pwdl-font-size-caption,0.75rem);font-weight:850}.saas-users__identity>div{min-width:0;overflow-wrap:anywhere}.saas-users__identity strong,.saas-users__identity small{display:block}.saas-users__identity strong{color:var(--su-navy);font-size:var(--pwdl-font-size-body,0.875rem)}.saas-users__identity small{margin-top:3px;color:var(--su-muted);font-size:var(--pwdl-font-size-caption,0.75rem)}.saas-users__calling{color:#64748b}.saas-users__calling.is-enabled{color:#0f766e;font-weight:800}.saas-users__status{display:inline-flex;align-items:center;gap:6px;font-weight:800;white-space:nowrap}.saas-users__status i{width:6px;height:6px;border-radius:999px;background:currentColor}.saas-users__status.is-active{color:#067647}.saas-users__status.is-invited{color:#b45309}.saas-users__status.is-inactive{color:#64748b}.saas-users__icon-button{display:inline-flex;width:34px;height:34px;align-items:center;justify-content:center;border:1px solid #cfd9e6;border-radius:6px;background:#fff;color:#52637a;text-decoration:none}.saas-users__icon-button:hover{border-color:#99d5d1;color:#0f766e}.saas-users__icon-button svg{width:15px;height:15px}.saas-users__pagination{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 14px;border-top:1px solid var(--su-border);color:var(--su-muted);font-size:var(--pwdl-font-size-caption,0.75rem)}.saas-users__pagination>div{display:flex;align-items:center;gap:9px}.saas-users__pagination button{display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border:1px solid #cfd9e6;border-radius:6px;background:#fff;color:#475569;cursor:pointer}.saas-users__pagination button:disabled{cursor:not-allowed;opacity:.45}.saas-users__pagination button svg{width:14px;height:14px}.saas-users__pagination strong{color:#475569;font-size:var(--pwdl-font-size-caption,0.75rem)}.saas-users__empty{display:flex;min-height:240px;align-items:center;justify-content:center;flex-direction:column;gap:7px;padding:28px;color:var(--su-muted);text-align:center}.saas-users__empty>svg{width:28px;height:28px;color:#0f766e}.saas-users__empty strong{color:var(--su-navy);font-size:var(--pwdl-font-size-body,0.875rem)}.saas-users__empty span{font-size:var(--pwdl-font-size-caption,0.75rem)}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}@media(max-width:900px){.saas-users__summary{grid-template-columns:repeat(2,1fr)}.saas-users__summary>div:nth-child(2){border-right:0}.saas-users__summary>div:nth-child(-n+2){border-bottom:1px solid var(--su-border)}}@media(max-width:680px){.saas-users__summary{grid-template-columns:1fr}.saas-users__summary>div{border-right:0;border-bottom:1px solid var(--su-border)}.saas-users__summary>div:last-child{border-bottom:0}.saas-users__toolbar{align-items:stretch;flex-direction:column}.saas-users__search{width:100%}.saas-users__filter{justify-content:space-between}.saas-users__filter select{width:100%}.saas-users__pagination{align-items:flex-start;flex-direction:column}}
    </style>
</x-filament-panels::page>
