<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit">
                Create Tenant
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
