<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-3 flex-wrap">
            <x-filament::button type="submit" color="primary" size="lg">
                Save Branding
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.email-branding.preview') }}"
                target="_blank"
                color="info"
                icon="heroicon-o-eye"
            >
                Preview Email
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
