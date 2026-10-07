<?php

namespace App\Providers;

use App\Models\CultivationCycle;
use App\Models\Farm;
use App\Models\Location;
use App\Models\Pond;
use App\Policies\CultivationCyclePolicy;
use App\Policies\FarmPolicy;
use App\Policies\LocationPolicy;
use App\Policies\PondPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(CultivationCycle::class, CultivationCyclePolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(Pond::class, PondPolicy::class);
        $this->configureDefaults();
        Gate::policy(Farm::class, FarmPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
