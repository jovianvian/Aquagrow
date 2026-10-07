@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="AquaGrow" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-teal-700 text-white dark:bg-teal-300 dark:text-teal-950">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="AquaGrow" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-teal-700 text-white dark:bg-teal-300 dark:text-teal-950">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:brand>
@endif
