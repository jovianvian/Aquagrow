<?php

use App\Http\Controllers\CultivationCycleController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PondController;
use App\Services\ActiveFarmContext;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (ActiveFarmContext $context) {
        $user = request()->user();
        $farms = $user->farms()->orderBy('name')->get();
        $activeFarm = $context->current($user);

        $summary = ['locations' => 0, 'ponds' => 0, 'planned_cycles' => 0, 'active_cycles' => 0, 'empty_ponds' => 0, 'maintenance_ponds' => 0];
        if ($activeFarm) {
            $summary = ['locations' => $activeFarm->locations()->count(), 'ponds' => $activeFarm->ponds()->count(), 'planned_cycles' => $activeFarm->cultivationCycles()->where('status', 'PLANNED')->count(), 'active_cycles' => $activeFarm->cultivationCycles()->where('status', 'ACTIVE')->count(), 'empty_ponds' => $activeFarm->ponds()->where('operational_status', 'AVAILABLE')->whereDoesntHave('cultivationCycles', fn ($query) => $query->whereIn('status', ['PLANNED', 'ACTIVE']))->count(), 'maintenance_ponds' => $activeFarm->ponds()->where('operational_status', 'MAINTENANCE')->count()];
        }

        return view('dashboard', ['farms' => $farms, 'activeFarm' => $activeFarm, 'summary' => $summary]);
    })->name('dashboard');
    Route::get('farms/create', [FarmController::class, 'create'])->name('farms.create');
    Route::post('farms', [FarmController::class, 'store'])->middleware('throttle:farms')->name('farms.store');
    Route::post('farms/{farm}/select', [FarmController::class, 'switch'])->can('view', 'farm')->name('farms.select');
    Route::get('farms/{farm}/edit', [FarmController::class, 'edit'])->can('update', 'farm')->name('farms.edit');
    Route::put('farms/{farm}', [FarmController::class, 'update'])->can('update', 'farm')->name('farms.update');
    Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('locations/create', [LocationController::class, 'create'])->name('locations.create');
    Route::post('locations', [LocationController::class, 'store'])->middleware('throttle:farms')->name('locations.store');
    Route::get('locations/{location}', [LocationController::class, 'show'])->can('view', 'location')->name('locations.show');
    Route::get('locations/{location}/edit', [LocationController::class, 'edit'])->can('update', 'location')->name('locations.edit');
    Route::put('locations/{location}', [LocationController::class, 'update'])->can('update', 'location')->name('locations.update');
    Route::get('ponds', [PondController::class, 'index'])->name('ponds.index');
    Route::get('ponds/create', [PondController::class, 'create'])->name('ponds.create');
    Route::post('ponds', [PondController::class, 'store'])->middleware('throttle:farms')->name('ponds.store');
    Route::get('ponds/{pond}', [PondController::class, 'show'])->can('view', 'pond')->name('ponds.show');
    Route::get('ponds/{pond}/edit', [PondController::class, 'edit'])->can('update', 'pond')->name('ponds.edit');
    Route::put('ponds/{pond}', [PondController::class, 'update'])->can('update', 'pond')->name('ponds.update');
    Route::get('cultivation-cycles', [CultivationCycleController::class, 'index'])->name('cultivation-cycles.index');
    Route::get('cultivation-cycles/create', [CultivationCycleController::class, 'create'])->name('cultivation-cycles.create');
    Route::post('cultivation-cycles', [CultivationCycleController::class, 'store'])->middleware('throttle:farms')->name('cultivation-cycles.store');
    Route::get('cultivation-cycles/{cultivationCycle}', [CultivationCycleController::class, 'show'])->name('cultivation-cycles.show');
    Route::get('cultivation-cycles/{cultivationCycle}/edit', [CultivationCycleController::class, 'edit'])->name('cultivation-cycles.edit');
    Route::put('cultivation-cycles/{cultivationCycle}', [CultivationCycleController::class, 'update'])->name('cultivation-cycles.update');
    Route::post('cultivation-cycles/{cultivationCycle}/start', [CultivationCycleController::class, 'start'])->middleware('throttle:farms')->name('cultivation-cycles.start');
    Route::post('cultivation-cycles/{cultivationCycle}/cancel', [CultivationCycleController::class, 'cancel'])->name('cultivation-cycles.cancel');
});

require __DIR__.'/settings.php';
