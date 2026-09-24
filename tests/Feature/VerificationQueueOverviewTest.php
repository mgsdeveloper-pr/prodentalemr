<?php

use App\Filament\Admin\Widgets\ManagedServicesQuickLinks;
use Livewire\Livewire;

it('renders queue totals and toggles the existing attention filter', function (): void {
    Livewire::test(ManagedServicesQuickLinks::class)
        ->assertSee('Work Queues')
        ->assertSee('New & Pending')
        ->assertSee('Urgent Requests')
        ->assertSeeHtml('aria-pressed="false"')
        ->call('applyFilter', 'urgent_requests')
        ->assertSet('activeFilter', 'urgent_requests')
        ->assertSeeHtml('aria-pressed="true"')
        ->assertDispatched('verification-attention-filter-changed', filter: 'urgent_requests')
        ->call('applyFilter', 'urgent_requests')
        ->assertSet('activeFilter', null)
        ->assertDispatched('verification-attention-filter-changed', filter: null);
});
