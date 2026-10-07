<?php

namespace App\Http\Controllers;

use App\Exceptions\CultivationCycleConflict;
use App\Http\Requests\StartCultivationCycleRequest;
use App\Http\Requests\StoreCultivationCycleRequest;
use App\Http\Requests\UpdateCultivationCycleRequest;
use App\Models\CultivationCycle;
use App\Models\FishSpecies;
use App\Services\ActiveFarmContext;
use App\Services\CultivationCycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CultivationCycleController extends Controller
{
    public function index(ActiveFarmContext $context): View|RedirectResponse
    {
        $farm = $context->current(request()->user());
        if (! $farm) {
            return to_route('dashboard');
        }

        return view('cultivation-cycles.index', ['farm' => $farm, 'cycles' => $farm->cultivationCycles()->with(['pond.location', 'fishSpecies'])->latest()->paginate(15)]);
    }

    public function create(ActiveFarmContext $context): View|RedirectResponse
    {
        $farm = $context->current(request()->user());
        if (! $farm) {
            return to_route('dashboard');
        } Gate::authorize('create', [CultivationCycle::class, $farm]);

        return view('cultivation-cycles.create', ['farm' => $farm, 'ponds' => $farm->ponds()->where('operational_status', '!=', 'INACTIVE')->orderBy('name')->get(), 'species' => FishSpecies::where('is_active', true)->orderBy('common_name')->get()]);
    }

    public function store(StoreCultivationCycleRequest $request, ActiveFarmContext $context, CultivationCycleService $service): RedirectResponse
    {
        $farm = $context->current($request->user());
        if (! $farm) {
            return to_route('dashboard');
        } Gate::authorize('create', [CultivationCycle::class, $farm]);
        try {
            $cycle = $service->createPlanned($request->user(), $farm, $request->validated());
        } catch (CultivationCycleConflict $e) {
            return back()->withErrors(['pond_id' => $e->getMessage()])->withInput();
        }

        return to_route('cultivation-cycles.show', $cycle)->with('status', 'Cycle planned.');
    }

    public function show(CultivationCycle $cultivationCycle): View
    {
        Gate::authorize('view', $cultivationCycle);

        return view('cultivation-cycles.show', ['cycle' => $cultivationCycle->load(['pond', 'fishSpecies'])]);
    }

    public function edit(CultivationCycle $cultivationCycle): View
    {
        Gate::authorize('update', $cultivationCycle);

        return view('cultivation-cycles.edit', ['cycle' => $cultivationCycle, 'species' => FishSpecies::where('is_active', true)->orderBy('common_name')->get()]);
    }

    public function update(UpdateCultivationCycleRequest $request, CultivationCycle $cultivationCycle, CultivationCycleService $service): RedirectResponse
    {
        Gate::authorize('update', $cultivationCycle);
        try {
            $service->updatePlanned($cultivationCycle, $request->validated());
        } catch (CultivationCycleConflict $e) {
            return back()->withErrors(['cycle' => $e->getMessage()])->withInput();
        }

        return to_route('cultivation-cycles.show', $cultivationCycle)->with('status', 'Cycle updated.');
    }

    public function start(StartCultivationCycleRequest $request, CultivationCycle $cultivationCycle, CultivationCycleService $service): RedirectResponse
    {
        Gate::authorize('start', $cultivationCycle);
        try {
            $service->start($cultivationCycle, $request->validated());
        } catch (CultivationCycleConflict $e) {
            return back()->withErrors(['stocked_at' => $e->getMessage()])->withInput();
        }

        return to_route('cultivation-cycles.show', $cultivationCycle)->with('status', 'Cycle started.');
    }

    public function cancel(CultivationCycle $cultivationCycle, CultivationCycleService $service): RedirectResponse
    {
        Gate::authorize('cancel', $cultivationCycle);
        try {
            $service->cancel($cultivationCycle);
        } catch (CultivationCycleConflict $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }

        return back()->with('status', 'Cycle cancelled.');
    }
}
