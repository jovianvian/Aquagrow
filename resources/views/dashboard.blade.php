<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if ($farms->isEmpty())
            <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-8 dark:border-cyan-900 dark:bg-cyan-950" role="status"><flux:heading size="lg">Welcome to AquaGrow AI</flux:heading><flux:subheading class="mt-2">No farm has been added yet. Create one to start managing your workspace.</flux:subheading><flux:button class="mt-6" variant="primary" href="{{ route('farms.create') }}">Create a farm</flux:button></div>
        @elseif ($activeFarm === null)
            <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-8 dark:border-cyan-900 dark:bg-cyan-950" role="status"><flux:heading size="lg">Choose a farm to continue</flux:heading><flux:subheading class="mt-2">You belong to multiple farms. Select the workspace you want to open.</flux:subheading><div class="mt-6 flex flex-wrap gap-3">@foreach($farms as $farm)<form method="POST" action="{{ route('farms.select',$farm) }}">@csrf<button class="rounded-md border border-cyan-700 bg-white px-4 py-3 text-sm font-medium text-cyan-950 hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-700" type="submit">{{ $farm->name }}</button></form>@endforeach</div></div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-4"><div><flux:heading size="xl">{{ $activeFarm->name }}</flux:heading><flux:subheading>Farm workspace · {{ $activeFarm->timezone }}</flux:subheading></div><div class="flex flex-wrap gap-2">@foreach($farms as $farm)<form method="POST" action="{{ route('farms.select',$farm) }}">@csrf<button class="rounded-md border px-3 py-2 text-sm" type="submit" aria-pressed="{{ $farm->is($activeFarm) ? 'true' : 'false' }}">{{ $farm->name }}</button></form>@endforeach @can('update',$activeFarm)<flux:button href="{{ route('farms.edit',$activeFarm) }}">Farm settings</flux:button>@endcan</div></div>
            <div class="rounded-xl border border-neutral-200 p-8 dark:border-neutral-700"><flux:heading size="lg">Your farm workspace</flux:heading><flux:subheading class="mt-2">Manage locations and ponds before recording cultivation data.</flux:subheading><div class="mt-6 grid gap-4 sm:grid-cols-3"><div><span class="text-sm text-neutral-500">{{ $activeFarm->locations()->count() }} location</span></div><div><span class="text-sm text-neutral-500">{{ $activeFarm->ponds()->count() }} pond</span></div><div><span class="text-sm text-neutral-500">Available ponds</span><p class="text-2xl font-semibold">{{ $activeFarm->ponds()->where('operational_status', 'AVAILABLE')->count() }}</p></div></div><div class="mt-6 flex gap-3"><flux:button href="{{ route('locations.index') }}">Locations</flux:button><flux:button href="{{ route('ponds.index') }}">Ponds</flux:button></div></div>
        @endif
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts::app>
