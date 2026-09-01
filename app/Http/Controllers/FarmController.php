<?php

namespace App\Http\Controllers;

use App\Enums\FarmMembershipRole;
use App\Http\Requests\StoreFarmRequest;
use App\Models\Farm;
use App\Services\ActiveFarmContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FarmController extends Controller
{
    public function create(): View
    {
        return view('farms.create');
    }

    public function store(StoreFarmRequest $request, ActiveFarmContext $context): RedirectResponse
    {
        $farm = DB::transaction(function () use ($request) {
            $base = Str::slug($request->validated('name')) ?: 'farm';
            $base = Str::substr($base, 0, 140);
            $slug = $base;
            $n = 2;
            while (Farm::where('slug', $slug)->exists()) {
                $slug = $base.'-'.($n++);
            }$farm = Farm::create([...$request->validated(), 'slug' => $slug]);
            $farm->members()->attach($request->user()->id, ['role' => FarmMembershipRole::FARM_OWNER->value]);

            return $farm;
        });
        $context->select($request->user(), $farm);

        return to_route('dashboard')->with('status', 'Farm berhasil dibuat.');
    }

    public function switch(Farm $farm, ActiveFarmContext $context): RedirectResponse
    {
        abort_unless($context->select(request()->user(), $farm), 403);

        return back()->with('status', 'Farm aktif diperbarui.');
    }

    public function edit(Farm $farm): View
    {
        return view('farms.edit', compact('farm'));
    }

    public function update(StoreFarmRequest $request, Farm $farm): RedirectResponse
    {
        $farm->update($request->validated());

        return to_route('dashboard')->with('status', 'Farm diperbarui.');
    }
}
