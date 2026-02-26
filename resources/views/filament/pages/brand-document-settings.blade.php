<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-3 flex-wrap">
            <x-filament::button type="submit" color="primary" size="lg">
                💾 Save Brand Settings
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.email-templates.preview.first') }}"
                target="_blank"
                color="info"
                icon="heroicon-o-eye"
            >
                Preview Email
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.voucher.preview') }}"
                target="_blank"
                color="success"
                icon="heroicon-o-document-text"
            >
                Preview PDF Voucher
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
