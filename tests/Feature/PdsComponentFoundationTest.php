<?php

use Illuminate\Support\Facades\Blade;

it('renders reusable pds layout, button, status, card, and form components', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-pds.page-container>
            <x-pds.stack>
                <x-pds.workspace-title title="Verification Workspace" description="Operational queue" />
                <x-pds.card title="Plan Status">
                    <x-pds.status-pill status="success">Complete</x-pds.status-pill>
                    <x-pds.badge color="teal">Active</x-pds.badge>
                    <x-pds.progress-indicator value="65" label="Completion" />
                    <x-pds.priority-indicator priority="high">High</x-pds.priority-indicator>
                </x-pds.card>
                <x-pds.form-section title="Eligibility">
                    <x-pds.field-group label="Member ID" helper="Use payer member identifier.">
                        <x-pds.readonly-display value="12345" />
                    </x-pds.field-group>
                    <x-pds.helper-text>Helper copy</x-pds.helper-text>
                </x-pds.form-section>
                <x-pds.action-toolbar>
                    <x-pds.button>Save</x-pds.button>
                    <x-pds.button variant="secondary">Cancel</x-pds.button>
                    <x-pds.icon-button label="Refresh">R</x-pds.icon-button>
                </x-pds.action-toolbar>
            </x-pds.stack>
        </x-pds.page-container>
    BLADE);

    expect($html)
        ->toContain('pds-page-container')
        ->toContain('pds-workspace-title')
        ->toContain('pds-card')
        ->toContain('pds-status-pill')
        ->toContain('pds-form-section')
        ->toContain('pds-button');
});

it('renders reusable pds table, feedback, loading, and container components', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-pds.table-toolbar>
            <x-pds.search-header placeholder="Search requests" />
            <x-pds.filter-bar>
                <x-pds.badge>Open</x-pds.badge>
            </x-pds.filter-bar>
        </x-pds.table-toolbar>
        <x-pds.bulk-action-bar>2 selected</x-pds.bulk-action-bar>
        <x-pds.empty-state title="No records" description="Try a different filter." />
        <x-pds.loading-state label="Loading records" />
        <x-pds.skeleton-loader lines="2" />
        <x-pds.page-loader label="Loading page" />
        <x-pds.empty-placeholder />
        <x-pds.breadcrumb :items="[['label' => 'Verification', 'url' => '/verification'], ['label' => 'Requests']]" />
        <x-pds.alert title="Saved">Record saved.</x-pds.alert>
        <x-pds.banner type="warning">Review required.</x-pds.banner>
        <x-pds.inline-message type="success">Ready</x-pds.inline-message>
        <x-pds.toast title="Updated">Done</x-pds.toast>
        <x-pds.confirmation-dialog title="Confirm">Continue?</x-pds.confirmation-dialog>
        <x-pds.drawer title="Details">Panel</x-pds.drawer>
        <x-pds.slide-panel title="Slide">Panel</x-pds.slide-panel>
        <x-pds.modal title="Modal">Panel</x-pds.modal>
        <x-pds.side-panel title="Context">Panel</x-pds.side-panel>
    BLADE);

    expect($html)
        ->toContain('pds-table-toolbar')
        ->toContain('pds-empty-state')
        ->toContain('pds-loading-state')
        ->toContain('pds-breadcrumb')
        ->toContain('pds-alert')
        ->toContain('pds-confirmation-dialog')
        ->toContain('pds-drawer')
        ->toContain('pds-modal');
});
