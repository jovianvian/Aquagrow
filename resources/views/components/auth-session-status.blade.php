@props([
    'status',
])

@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'rounded-lg border border-emerald-700 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-950 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-100']) }}>
        {{ $status }}
    </div>
@endif
