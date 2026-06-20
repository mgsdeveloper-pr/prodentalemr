@php
    $users = $this->getUsers();
@endphp

<x-filament-panels::page>
    <style>
        .dso-users { display:grid; gap:24px; max-width:none; }
        .dso-users-card { border:1px solid #dbe4ee; border-radius:26px; background:#fff; box-shadow:0 16px 34px rgba(15,23,42,.06); overflow:hidden; }
        .dso-users-hero { padding:28px 32px; background:linear-gradient(135deg,#fff,#f8fbff); }
        .dso-pill { display:inline-flex; border:1px solid #bfdbfe; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:850; letter-spacing:.14em; padding:8px 14px; text-transform:uppercase; }
        .dso-title { margin:16px 0 8px; color:#020617; font-size:36px; line-height:1; font-weight:900; letter-spacing:-.04em; }
        .dso-copy { margin:0; color:#52637a; font-size:15px; line-height:1.7; }
        .dso-grid { display:grid; grid-template-columns:1.15fr .85fr; gap:24px; }
        .dso-table { width:100%; border-collapse:collapse; }
        .dso-table th { background:#f8fafc; color:#64748b; font-size:12px; font-weight:850; letter-spacing:.12em; padding:16px 20px; text-align:left; text-transform:uppercase; }
        .dso-table td { border-top:1px solid #edf2f7; color:#334155; font-size:14px; padding:18px 20px; }
        .dso-name { color:#0f172a; font-weight:850; }
        .dso-muted { margin-top:4px; color:#64748b; font-size:13px; }
        .dso-form { padding:22px; }
        .dso-field { margin-bottom:16px; }
        .dso-label { display:block; margin-bottom:7px; color:#64748b; font-size:12px; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
        .dso-input { width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:12px 14px; color:#0f172a; }
        .dso-button { display:inline-flex; justify-content:center; border:0; border-radius:16px; background:#0f766e; color:#fff; font-weight:850; padding:13px 18px; cursor:pointer; }
        .dso-empty { padding:42px 24px; color:#64748b; text-align:center; }
        @media (max-width:1100px){ .dso-grid{grid-template-columns:1fr;} .dso-users-card{overflow-x:auto;} .dso-table{min-width:720px;} }
    </style>

    <div class="dso-users">
        <section class="dso-users-card">
            <div class="dso-users-hero">
                <div class="dso-pill">Access Management</div>
                <h1 class="dso-title">DSO Users</h1>
                <p class="dso-copy">Invite DSO admins, managers, and viewers to manage the enterprise network from the DSO workspace.</p>
            </div>
        </section>

        <div class="dso-grid">
            <section class="dso-users-card">
                @if ($users->isEmpty())
                    <div class="dso-empty">No DSO users found yet.</div>
                @else
                    <table class="dso-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <div class="dso-name">{{ $user->name }}</div>
                                        <div class="dso-muted">{{ $user->email }}</div>
                                    </td>
                                    <td>{{ $user->getPrimaryRoleLabel() }}</td>
                                    <td>{{ $user->status ? 'Active' : 'Inactive' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="dso-users-card">
                <form class="dso-form" wire:submit="createUser">
                    <div class="dso-pill">Invite User</div>

                    <div class="dso-field" style="margin-top:18px;">
                        <label class="dso-label">Full name</label>
                        <input class="dso-input" type="text" wire:model="name">
                        @error('name') <div class="dso-muted">{{ $message }}</div> @enderror
                    </div>

                    <div class="dso-field">
                        <label class="dso-label">Email</label>
                        <input class="dso-input" type="email" wire:model="email">
                        @error('email') <div class="dso-muted">{{ $message }}</div> @enderror
                    </div>

                    <div class="dso-field">
                        <label class="dso-label">Phone</label>
                        <input class="dso-input" type="text" wire:model="phone">
                        @error('phone') <div class="dso-muted">{{ $message }}</div> @enderror
                    </div>

                    <div class="dso-field">
                        <label class="dso-label">Role</label>
                        <select class="dso-input" wire:model="role">
                            @foreach ($this->getRoleOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role') <div class="dso-muted">{{ $message }}</div> @enderror
                    </div>

                    <div class="dso-field">
                        <label class="dso-label">Temporary password</label>
                        <input class="dso-input" type="password" wire:model="password">
                        @error('password') <div class="dso-muted">{{ $message }}</div> @enderror
                    </div>

                    <div class="dso-field">
                        <label class="dso-label">Confirm password</label>
                        <input class="dso-input" type="password" wire:model="password_confirmation">
                    </div>

                    <button class="dso-button" type="submit" @disabled(! $this->canCreateUsers())>
                        Invite DSO User
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-filament-panels::page>
