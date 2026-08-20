<?php

use Illuminate\Support\Facades\Blade;

it('renders the reusable pds workspace shell regions', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-pds.workspace-shell label="Verification Workspace">
            <x-pds.workspace-header
                eyebrow="Verification"
                title="Verification Workspace"
                description="Operational queue and work context."
            >
                <x-slot:actions>
                    <x-pds.button>New Request</x-pds.button>
                </x-slot:actions>
            </x-pds.workspace-header>

            <x-pds.workspace-toolbar>
                <span>Toolbar</span>
            </x-pds.workspace-toolbar>

            <x-pds.workspace-body>
                <x-pds.workspace-left-panel>Context</x-pds.workspace-left-panel>
                <x-pds.workspace-center>Primary work</x-pds.workspace-center>
                <x-pds.workspace-right-panel>Awareness</x-pds.workspace-right-panel>
            </x-pds.workspace-body>

            <x-pds.workspace-footer>Footer</x-pds.workspace-footer>
        </x-pds.workspace-shell>
    BLADE);

    expect($html)
        ->toContain('pds-workspace-shell')
        ->toContain('aria-label="Verification Workspace"')
        ->toContain('pds-workspace-header')
        ->toContain('pds-workspace-header__actions')
        ->toContain('pds-workspace-toolbar')
        ->toContain('pds-workspace-body')
        ->toContain('pds-workspace-panel--left')
        ->toContain('pds-workspace-center')
        ->toContain('pds-workspace-panel--right')
        ->toContain('pds-workspace-footer');
});

it('publishes workspace shell layout tokens in the filament shell styles', function (): void {
    $styles = view('filament.appshell.styles')->render();

    expect($styles)
        ->toContain('--pwdl-layout-left: 300px')
        ->toContain('--pwdl-layout-right: 320px')
        ->toContain('pds-workspace-shell')
        ->toContain('pds-workspace-body')
        ->toContain('pds-workspace-panel--right')
        ->toContain('grid-template-columns: var(--pwdl-layout-left) minmax(0, 1fr) var(--pwdl-layout-right)');
});
