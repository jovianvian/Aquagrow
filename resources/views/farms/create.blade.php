<x-layouts::app :title="__('Create farm')">
    <div class="mx-auto w-full max-w-2xl space-y-6"><div><flux:heading size="xl">Create your first farm</flux:heading><flux:subheading>Set up your AquaGrow workspace.</flux:subheading></div>
    <form method="POST" action="{{ route('farms.store') }}" class="space-y-5">@csrf
        <flux:input name="name" label="Farm name" value="{{ old('name') }}" required maxlength="120" />
        <flux:input name="timezone" label="Timezone" value="{{ old('timezone','Asia/Jakarta') }}" required maxlength="64" />
        <flux:select name="currency" label="Currency"><option value="IDR" @selected(old('currency','IDR') === 'IDR')>IDR — Indonesian Rupiah</option></flux:select>
        @if($errors->any())<div role="alert" class="text-sm text-red-700">{{ $errors->first() }}</div>@endif
        <flux:button type="submit" variant="primary">Create farm</flux:button>
    </form></div>
</x-layouts::app>
