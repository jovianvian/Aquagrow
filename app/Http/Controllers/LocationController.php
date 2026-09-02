<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Models\Location;
use App\Services\ActiveFarmContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(ActiveFarmContext $context): View|RedirectResponse
    {
        $farm = $context->current(request()->user());
        if (! $farm) {
            return to_route('dashboard');
        }

        return view('locations.index', ['farm' => $farm, 'locations' => $farm->locations()->withCount('ponds')->orderBy('name')->get()]);
    }

    public function create(ActiveFarmContext $context): View|RedirectResponse
    {
        return $this->farmOrRedirect($context);
    }

    public function store(StoreLocationRequest $request, ActiveFarmContext $context): RedirectResponse
    {
        $farm = $context->current($request->user());
        if (! $farm) {
            return to_route('dashboard');
        }
        abort_unless($request->user()->can('update', $farm), 403);
        $data = $request->validated();
        $base = Str::limit(Str::slug(trim($data['name'])) ?: 'location', 150, '');
        $slug = $base;
        $suffix = 2;
        while ($farm->locations()->where('slug', $slug)->exists() && $suffix <= 999) {
            $slug = Str::limit($base, 156 - strlen((string) $suffix), '').'-'.$suffix++;
        }
        if ($farm->locations()->where('slug', $slug)->exists()) {
            abort(422);
        }
        $farm->locations()->create([...$data, 'slug' => $slug]);

        return to_route('locations.index')->with('status', 'Location created.');
    }

    public function show(Location $location): View
    {
        Gate::authorize('view', $location);

        return view('locations.show', ['location' => $location->load('ponds')]);
    }

    public function edit(Location $location): View
    {
        Gate::authorize('update', $location);

        return view('locations.edit', compact('location'));
    }

    public function update(StoreLocationRequest $request, Location $location): RedirectResponse
    {
        Gate::authorize('update', $location);
        $location->update($request->validated());

        return to_route('locations.show', $location)->with('status', 'Location updated.');
    }

    private function farmOrRedirect(ActiveFarmContext $context): View|RedirectResponse
    {
        $farm = $context->current(request()->user());
        if (! $farm) {
            return to_route('dashboard');
        }
        abort_unless(request()->user()->can('update', $farm), 403);

        return view('locations.create', compact('farm'));
    }
}
