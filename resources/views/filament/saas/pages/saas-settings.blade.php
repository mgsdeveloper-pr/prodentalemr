<x-filament-panels::page>
    <style>
        .saas-settings-shell { display:grid; gap:24px; }
        .saas-settings-tools { border:1px solid #dbe4ee; border-radius:28px; background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%); box-shadow:0 22px 48px rgba(15,23,42,.07); overflow:hidden; }
        .saas-settings-tools-head { padding:24px 28px; border-bottom:1px solid #e5edf5; }
        .saas-settings-tools-eyebrow { margin:0 0 8px; color:#0f766e; font-size:12px; font-weight:900; letter-spacing:.16em; text-transform:uppercase; }
        .saas-settings-tools-title { margin:0; color:#0f172a; font-size:28px; font-weight:900; }
        .saas-settings-tools-copy { margin:10px 0 0; color:#64748b; font-size:14px; line-height:1.75; max-width:70ch; }
        .saas-settings-tools-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; padding:24px 28px 28px; }
        .saas-settings-tool { border:1px solid #dbe4ee; border-radius:22px; background:#fff; padding:20px; text-decoration:none; box-shadow:0 14px 30px rgba(15,23,42,.05); transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease; }
        .saas-settings-tool:hover { transform:translateY(-2px); box-shadow:0 18px 36px rgba(15,23,42,.08); }
        .saas-settings-tool-eyebrow { margin:0 0 10px; font-size:12px; font-weight:900; letter-spacing:.16em; text-transform:uppercase; }
        .saas-settings-tool-title { margin:0; color:#0f172a; font-size:20px; font-weight:900; }
        .saas-settings-tool-copy { margin:10px 0 0; color:#64748b; font-size:14px; line-height:1.7; }
        .saas-settings-tool-link { margin-top:14px; display:inline-flex; align-items:center; gap:8px; color:#0f172a; font-size:13px; font-weight:800; }
        .tone-amber:hover { border-color:#fdba74; }
        .tone-amber .saas-settings-tool-eyebrow { color:#b45309; }
        .tone-blue:hover { border-color:#93c5fd; }
        .tone-blue .saas-settings-tool-eyebrow { color:#2563eb; }
        .tone-emerald:hover { border-color:#86efac; }
        .tone-emerald .saas-settings-tool-eyebrow { color:#047857; }
        .tone-rose:hover { border-color:#fda4af; }
        .tone-rose .saas-settings-tool-eyebrow { color:#be123c; }
        .tone-violet:hover { border-color:#c4b5fd; }
        .tone-violet .saas-settings-tool-eyebrow { color:#7c3aed; }
        .tone-slate:hover { border-color:#cbd5e1; }
        .tone-slate .saas-settings-tool-eyebrow { color:#475569; }
        .tone-cyan:hover { border-color:#67e8f9; }
        .tone-cyan .saas-settings-tool-eyebrow { color:#0891b2; }
        .saas-settings-form { border:1px solid #dbe4ee; border-radius:28px; background:#fff; box-shadow:0 18px 40px rgba(15,23,42,.06); overflow:hidden; }
        .saas-settings-form-head { padding:22px 28px; border-bottom:1px solid #e5edf5; }
        .saas-settings-form-eyebrow { margin:0 0 8px; color:#1d4ed8; font-size:12px; font-weight:900; letter-spacing:.16em; text-transform:uppercase; }
        .saas-settings-form-title { margin:0; color:#0f172a; font-size:24px; font-weight:900; }
        .saas-settings-form-copy { margin:8px 0 0; color:#64748b; font-size:14px; line-height:1.7; }
        .saas-settings-form-body { padding:24px 28px 28px; }
        @media (max-width: 1100px) {
            .saas-settings-tools-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media (max-width: 720px) {
            .saas-settings-tools-grid { grid-template-columns:1fr; padding:20px; }
            .saas-settings-tools-head, .saas-settings-form-head, .saas-settings-form-body { padding-left:20px; padding-right:20px; }
        }
    </style>

    <div class="saas-settings-shell">
        <section class="saas-settings-tools">
            <div class="saas-settings-tools-head">
                <p class="saas-settings-tools-eyebrow">Settings Workspace</p>
                <h2 class="saas-settings-tools-title">Open the right admin setting fast.</h2>
                <p class="saas-settings-tools-copy">
                    Keep payment setup, billing rules, ADA/CDT imports, notifications, and access control grouped inside one clean SaaS settings area.
                </p>
            </div>

            <div class="saas-settings-tools-grid">
                @foreach($this->getSettingsTools() as $tool)
                    <a href="{{ $tool['url'] }}" class="saas-settings-tool tone-{{ $tool['tone'] }}">
                        <p class="saas-settings-tool-eyebrow">{{ $tool['eyebrow'] }}</p>
                        <h3 class="saas-settings-tool-title">{{ $tool['title'] }}</h3>
                        <p class="saas-settings-tool-copy">{{ $tool['description'] }}</p>
                        <span class="saas-settings-tool-link">
                            Open setting
                            <span aria-hidden="true">&rarr;</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="saas-settings-form">
            <div class="saas-settings-form-head">
                <p class="saas-settings-form-eyebrow">Platform Defaults</p>
                <h2 class="saas-settings-form-title">Core SaaS settings</h2>
                <p class="saas-settings-form-copy">
                    Manage platform branding, support details, timezone defaults, and global SaaS behavior here.
                </p>
            </div>

            <div class="saas-settings-form-body">
                <form wire:submit="save">
                    {{ $this->form }}
                </form>
            </div>
        </section>
    </div>
</x-filament-panels::page>
