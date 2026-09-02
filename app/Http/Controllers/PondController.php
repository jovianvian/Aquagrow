<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePondRequest;
use App\Models\Pond;
use App\Services\ActiveFarmContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PondController extends Controller
{
    public function index(ActiveFarmContext $context): View|RedirectResponse
    {
        $farm = $context->current(request()->user());
        if (! $farm) {
            return to_route('dashboard');
        }

        return view('ponds.index', ['farm' => $farm, 'ponds' => $farm->ponds()->with('location')->orderBy('name')->get()]);
    }

    public function create(ActiveFarmContext $context): View|RedirectResponse
    {
        $farm = $context->current(request()->user());
        if (! $farm) {
            return to_route('dashboard');
        }
        abort_unless(request()->user()->can('update', $farm), 403);

        return view('ponds.create', ['farm' => $farm, 'locations' => $farm->locations()->orderBy('name')->get()]);
    }

    public function store(StorePondRequest $request, ActiveFarmContext $context): RedirectResponse
    {
        $farm = $context->current($request->user());
        abort_unless($farm !== null, 403);
        abort_unless($request->user()->can('update', $farm), 403);
        $data = $request->validated();
        if (! $farm->locations()->whereKey($data['location_id'])->exists()) {
            return back()->withErrors(['location_id' => 'The selected location is not available in the active farm.'])->withInput();
        }
        if ($farm->ponds()->where('code', $data['code'])->exists()) {
            return back()->withErrors(['code' => 'This pond code is already used in the active farm.'])->withInput();
        }
        $farm->ponds()->create($data);

        return to_route('ponds.index')->with('status', 'Pond created.');
    }

    public function show(Pond $pond): View
    {
        Gate::authorize('view', $pond);

        return view('ponds.show', ['pond' => $pond->load('location')]);
    }

    public function edit(Pond $pond): View
    {
        Gate::authorize('update', $pond);

        return view('ponds.edit', ['pond' => $pond, 'locations' => $pond->farm->locations()->orderBy('name')->get()]);
    }

    public function update(StorePondRequest $request, Pond $pond): RedirectResponse
    {
        Gate::authorize('update', $pond);
        if (! $pond->farm->locations()->whereKey($request->validated('location_id'))->exists()) {
            return back()->withErrors(['location_id' => 'The selected location is not available in the pond farm.'])->withInput();
        }
        $pond->update($request->validated());

        return to_route('ponds.show', $pond)->with('status', 'Pond updated.');
    }
}
