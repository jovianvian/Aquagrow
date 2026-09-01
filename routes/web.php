<?php

use App\Http\Controllers\FarmController;
use App\Services\ActiveFarmContext;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (ActiveFarmContext $context) {
        $user = request()->user();

        return view('dashboard', ['farms' => $user->farms()->orderBy('name')->get(), 'activeFarm' => $context->current($user)]);
    })->name('dashboard');
    Route::get('farms/create', [FarmController::class, 'create'])->name('farms.create');
    Route::post('farms', [FarmController::class, 'store'])->middleware('throttle:farms')->name('farms.store');
    Route::post('farms/{farm}/select', [FarmController::class, 'switch'])->can('view', 'farm')->name('farms.select');
    Route::get('farms/{farm}/edit', [FarmController::class, 'edit'])->can('update', 'farm')->name('farms.edit');
    Route::put('farms/{farm}', [FarmController::class, 'update'])->can('update', 'farm')->name('farms.update');
});

require __DIR__.'/settings.php';
