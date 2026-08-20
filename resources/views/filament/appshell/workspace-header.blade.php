@php
    $workspaceLabel = match ($workspace) {
        'platform' => 'Platform Workspace',
        'verification' => 'Verification Workspace',
        'organization' => 'Organization Workspace',
        'clinic' => 'Clinic Workspace',
        'dso' => 'Organization Workspace',
        default => ucfirst((string) $workspace) . ' Workspace',
    };
@endphp

<div class="pd-appshell-workspace-header" aria-label="Workspace header">
    @include('filament.appshell.status-bar', ['items' => [$workspaceLabel]])
</div>
