@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-teal-700 text-white shadow-sm dark:bg-teal-300 dark:text-teal-950">
        <x-app-logo-icon class="size-8" />
    </div>
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-700 dark:text-teal-300">AquaGrow</p>
    <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
    <flux:subheading class="mx-auto max-w-sm">{{ $description }}</flux:subheading>
</div>
