<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <x-filament::button wire:click="setToday" size="sm" color="gray">Today</x-filament::button>
                <x-filament::button wire:click="setTomorrow" size="sm" color="gray">Tomorrow</x-filament::button>
                <x-filament::button wire:click="setThisWeek" size="sm" color="gray">This Week</x-filament::button>
                <x-filament::button wire:click="setThisMonth" size="sm" color="gray">This Month</x-filament::button>
                <x-filament::button wire:click="setLastMonth" size="sm" color="gray">Last Month</x-filament::button>
                <x-filament::button wire:click="setThisYear" size="sm" color="gray">This Year</x-filament::button>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">From:</label>
                    <input type="date" wire:model.live="from" class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">Until:</label>
                    <input type="date" wire:model.live="until" class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
