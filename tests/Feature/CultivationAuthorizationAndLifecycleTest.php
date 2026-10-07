<?php

namespace Tests\Feature;

use App\Enums\FarmMembershipRole;
use App\Enums\PondOperationalStatus;
use App\Models\CultivationCycle;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\FishSpecies;
use App\Models\Location;
use App\Models\Pond;
use App\Models\User;
use App\Policies\CultivationCyclePolicy;
use App\Services\ActiveFarmContext;
use App\Services\CultivationCycleService;
use App\Services\PondLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CultivationAuthorizationAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_show_receives_persisted_route_model_with_matching_context(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $operator = $this->member($farm, FarmMembershipRole::POND_OPERATOR);
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('aquagrow_testing', DB::selectOne('select current_database() as name')->name);
        $this->actingAs($operator)->withSession(['active_farm_id' => $farm->id]);
        $this->assertAuthenticatedAs($operator);
        $this->assertNotNull($operator->email_verified_at);
        $this->assertDatabaseHas('farm_memberships', ['farm_id' => $farm->id, 'user_id' => $operator->id, 'role' => FarmMembershipRole::POND_OPERATOR->value]);
        $this->assertTrue($cycle->exists);
        $this->assertNotNull($cycle->getKey());
        $this->assertSame($farm->id, $cycle->farm_id);
        $this->assertSame($farm->id, $pond->farm_id);
        $this->assertSame($farm->id, app(ActiveFarmContext::class)->current($operator)->id);
        $this->assertTrue(app(CultivationCyclePolicy::class)->view($operator, $cycle));
        $this->assertTrue(Gate::forUser($operator)->inspect('view', $cycle)->allowed());
        $observed = false;
        Gate::after(function ($user, $ability, $result, $arguments) use ($cycle, $farm, $operator, &$observed): void {
            if ($ability !== 'view' || ! ($arguments[0] instanceof CultivationCycle)) {
                return;
            }
            $bound = $arguments[0];
            $observed = true;
            $this->assertTrue($bound->exists);
            $this->assertSame($cycle->id, $bound->id);
            $this->assertSame($farm->id, $bound->farm_id);
            $this->assertSame($farm->id, $bound->pond->farm_id);
            $this->assertSame($bound, request()->route('cultivationCycle'));
            $this->assertSame($operator->id, $user->id);
            $this->assertSame($farm->id, app(ActiveFarmContext::class)->current($user)->id);
            $this->assertTrue($user->farmMemberships()->where('farm_id', $bound->farm_id)->exists());
            $this->assertTrue($result);
        });
        $this->get(route('cultivation-cycles.show', $cycle))->assertOk()->assertSee($cycle->batch_code);
        $this->assertTrue($observed);
    }

    public function test_owner_and_operator_can_read_planned_active_and_cancelled_cycles(): void
    {
        foreach (['PLANNED', 'ACTIVE', 'CANCELLED'] as $status) {
            [$owner, $farm, $pond, $cycle] = $this->fixture();
            $operator = $this->member($farm, FarmMembershipRole::POND_OPERATOR);
            if ($status === 'ACTIVE') {
                app(CultivationCycleService::class)->start($cycle, $this->startPayload($farm));
            } elseif ($status === 'CANCELLED') {
                app(CultivationCycleService::class)->cancel($cycle);
            }
            foreach ([$owner, $operator] as $user) {
                $this->actingAs($user)->withSession(['active_farm_id' => $farm->id]);
                $this->get(route('cultivation-cycles.index'))->assertOk()->assertSee($cycle->batch_code);
                $this->get(route('cultivation-cycles.show', $cycle))->assertOk()->assertSee($status);
            }
        }
    }

    public function test_operator_cannot_mutate_even_with_forged_owner_payload(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $operator = $this->member($farm, FarmMembershipRole::POND_OPERATOR);
        $before = $cycle->fresh()->getAttributes();
        $this->actingAs($operator)->withSession(['active_farm_id' => $farm->id]);
        $payload = ['pond_id' => $pond->id, 'fish_species_id' => $cycle->fish_species_id, 'batch_code' => 'FORGED', 'role' => 'FARM_OWNER', 'user_id' => $owner->id];
        $this->get(route('cultivation-cycles.create'))->assertForbidden();
        $this->post(route('cultivation-cycles.store'), $payload)->assertForbidden();
        $this->get(route('cultivation-cycles.edit', $cycle))->assertForbidden();
        $this->put(route('cultivation-cycles.update', $cycle), $payload)->assertForbidden();
        $this->post(route('cultivation-cycles.start', $cycle), $payload + $this->startPayload($farm))->assertForbidden();
        $this->post(route('cultivation-cycles.cancel', $cycle), $payload)->assertForbidden();
        $this->assertSame($before, $cycle->fresh()->getAttributes());
        $this->assertDatabaseCount('cultivation_cycles', 1);
    }

    public function test_owner_cancel_ignores_forged_fields_and_only_changes_cancellation_state(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $before = $cycle->getAttributes();

        $response = $this->actingAs($owner)
            ->withSession(['active_farm_id' => $farm->id])
            ->post(route('cultivation-cycles.cancel', $cycle), [
                'farm_id' => 999999,
                'pond_id' => 999999,
                'species_id' => 999999,
                'status' => 'ACTIVE',
                'stocked_at' => now()->toIso8601String(),
                'initial_fish_count' => 999,
                'initial_average_weight_g' => 99,
                'completion_date' => now()->toDateString(),
                'harvest_quantity' => 999,
                'unknown_field' => 'ignored',
            ]);

        $response->assertRedirect();
        $cancelled = $cycle->fresh();
        $this->assertSame('CANCELLED', $cancelled->getRawOriginal('status'));
        $this->assertSame($before['farm_id'], $cancelled->farm_id);
        $this->assertSame($before['pond_id'], $cancelled->pond_id);
        $this->assertSame($before['fish_species_id'], $cancelled->fish_species_id);
        $this->assertNull($cancelled->stocked_at);
        $this->assertNull($cancelled->initial_fish_count);
        $this->assertNull($cancelled->initial_average_weight_g);
        $this->assertSame($before['batch_code'], $cancelled->batch_code);
    }

    public function test_non_member_and_member_of_two_farms_cannot_access_cycle_outside_active_farm(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $other = Farm::factory()->create();
        FarmMembership::factory()->create(['farm_id' => $other->id, 'user_id' => $owner->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $before = $cycle->fresh()->getAttributes();
        foreach ([$owner, $outsider] as $user) {
            $this->actingAs($user)->withSession(['active_farm_id' => $other->id]);
            $this->get(route('cultivation-cycles.show', $cycle))->assertForbidden()->assertDontSee($cycle->batch_code);
            $this->get(route('cultivation-cycles.edit', $cycle))->assertForbidden();
            $this->put(route('cultivation-cycles.update', $cycle), ['fish_species_id' => $cycle->fish_species_id, 'batch_code' => 'ILLEGAL'])->assertForbidden();
            $this->post(route('cultivation-cycles.start', $cycle), $this->startPayload($farm))->assertForbidden();
            $this->post(route('cultivation-cycles.cancel', $cycle))->assertForbidden();
            $this->assertSame($before, $cycle->fresh()->getAttributes());
        }
    }

    public function test_planned_creation_uses_active_farm_and_ignores_unsupported_fields(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $other = Farm::factory()->create();
        $otherLocation = Location::factory()->create(['farm_id' => $other->id]);
        $otherPond = Pond::factory()->create(['farm_id' => $other->id, 'location_id' => $otherLocation->id]);
        $availablePond = Pond::factory()->create(['farm_id' => $farm->id, 'location_id' => $pond->location_id]);
        $species = FishSpecies::factory()->create(['is_active' => true]);
        $payload = ['pond_id' => $otherPond->id, 'fish_species_id' => $species->id, 'batch_code' => 'ABUSE-01', 'farm_id' => $other->id, 'status' => 'ACTIVE', 'location_id' => $otherLocation->id, 'user_id' => $other->id, 'created_by' => $other->id, 'stocked_at' => now()->toIso8601String(), 'initial_fish_count' => 99, 'initial_average_weight_g' => 2, 'completion_date' => now()->toDateString(), 'harvest_quantity' => 1, 'unknown_field' => 'ignored'];
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id]);
        $this->post(route('cultivation-cycles.store'), $payload)->assertSessionHasErrors('pond_id');
        $this->assertDatabaseMissing('cultivation_cycles', ['batch_code' => 'ABUSE-01']);

        $payload['pond_id'] = $availablePond->id;
        $this->post(route('cultivation-cycles.store'), $payload)->assertRedirect();
        $created = CultivationCycle::query()->where('batch_code', 'ABUSE-01')->firstOrFail();
        $this->assertSame($farm->id, $created->farm_id);
        $this->assertSame($availablePond->id, $created->pond_id);
        $this->assertSame('PLANNED', $created->getRawOriginal('status'));
        $this->assertNull($created->stocked_at);
        $this->assertNull($created->initial_fish_count);
        $this->assertNull($created->initial_average_weight_g);
    }

    public function test_update_and_start_reject_forged_state_fields_without_partial_mutation(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $before = $cycle->fresh()->getAttributes();
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id]);
        $this->put(route('cultivation-cycles.update', $cycle), ['fish_species_id' => $cycle->fish_species_id, 'batch_code' => 'UPDATED-01', 'farm_id' => 999999, 'pond_id' => 999999, 'status' => 'ACTIVE', 'stocked_at' => now()->toIso8601String(), 'initial_fish_count' => 2, 'initial_average_weight_g' => 1, 'completion_date' => now()->toDateString()])->assertRedirect();
        $updated = $cycle->fresh();
        $this->assertSame($farm->id, $updated->farm_id);
        $this->assertSame($pond->id, $updated->pond_id);
        $this->assertSame('PLANNED', $updated->getRawOriginal('status'));
        $this->assertNull($updated->initial_fish_count);
        $this->assertSame('UPDATED-01', $updated->batch_code);

        $snapshot = $updated->getAttributes();
        $this->post(route('cultivation-cycles.start', $updated), ['stocked_at' => now()->subHour()->toIso8601String(), 'initial_fish_count' => 0, 'initial_average_weight_g' => -1, 'farm_id' => 999999, 'pond_id' => 999999, 'status' => 'CANCELLED', 'species_id' => 999999])->assertSessionHasErrors();
        $this->assertSame($snapshot, $updated->fresh()->getAttributes());
        $this->assertSame($before['farm_id'], $updated->farm_id);
    }

    public function test_inactive_species_cycles_remain_readable_but_cannot_start_until_replaced(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $operator = $this->member($farm, FarmMembershipRole::POND_OPERATOR);
        $inactive = FishSpecies::factory()->create(['is_active' => false, 'code' => 'OLD-'.fake()->unique()->numberBetween(100, 999)]);
        $cycle->update(['fish_species_id' => $inactive->id]);
        $cycle->refresh();
        foreach ([$owner, $operator] as $user) {
            $this->actingAs($user)->withSession(['active_farm_id' => $farm->id]);
            $this->get(route('cultivation-cycles.index'))->assertOk()->assertSee($inactive->common_name);
            $this->get(route('cultivation-cycles.show', $cycle))->assertOk()->assertSee($inactive->common_name);
        }
        $before = $cycle->getAttributes();
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id]);
        $this->post(route('cultivation-cycles.start', $cycle), $this->startPayload($farm))->assertSessionHasErrors('stocked_at');
        $this->assertSame($before, $cycle->fresh()->getAttributes());

        $active = FishSpecies::query()->where('is_active', true)->firstOrFail();
        $this->put(route('cultivation-cycles.update', $cycle), ['fish_species_id' => $active->id, 'batch_code' => $cycle->batch_code])->assertRedirect();
        $this->post(route('cultivation-cycles.start', $cycle->fresh()), $this->startPayload($farm))->assertRedirect();
        $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'status' => 'ACTIVE', 'fish_species_id' => $active->id]);
    }

    public function test_active_cycle_remains_readable_after_species_is_deactivated(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $operator = $this->member($farm, FarmMembershipRole::POND_OPERATOR);
        app(CultivationCycleService::class)->start($cycle, $this->startPayload($farm));
        $active = $cycle->fresh();
        $species = $active->fishSpecies;
        $stocking = [$active->status->value, $active->stocked_at?->toIso8601String(), $active->initial_fish_count, $active->initial_average_weight_g];
        $species->update(['is_active' => false]);
        foreach ([$owner, $operator] as $user) {
            $this->actingAs($user)->withSession(['active_farm_id' => $farm->id]);
            $this->get(route('cultivation-cycles.index'))->assertOk()->assertSee($species->common_name);
            $this->get(route('cultivation-cycles.show', $active))->assertOk()->assertSee($species->common_name)->assertSee('ACTIVE');
            $start = $this->post(route('cultivation-cycles.start', $active), $this->startPayload($farm));
            if ($user->is($operator)) {
                $start->assertForbidden();
            } else {
                $start->assertSessionHasErrors('stocked_at');
            }
            $cancel = $this->post(route('cultivation-cycles.cancel', $active));
            if ($user->is($operator)) {
                $cancel->assertForbidden();
            } else {
                $cancel->assertSessionHasErrors('cycle');
            }
        }
        $fresh = $active->fresh();
        $this->assertSame($stocking, [$fresh->status->value, $fresh->stocked_at?->toIso8601String(), $fresh->initial_fish_count, $fresh->initial_average_weight_g]);
    }

    public function test_cycle_routes_enforce_owner_operator_and_non_member_matrix(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $operator = $this->member($farm, FarmMembershipRole::POND_OPERATOR);
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        foreach ([$operator, $outsider] as $user) {
            $index = $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])->get(route('cultivation-cycles.index'));
            $user === $operator ? $index->assertOk() : $index->assertRedirect(route('dashboard'));
            $response = $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])->get(route('cultivation-cycles.show', $cycle->id));
            $user === $operator ? $response->assertOk() : $response->assertForbidden();
            $response = $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])->post(route('cultivation-cycles.start', $cycle->id), $this->startPayload($farm));
            $response->assertForbidden();
        }
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->get(route('cultivation-cycles.create'))->assertOk();
    }

    public function test_missing_or_stale_active_farm_does_not_fallback_for_cycle_workspace(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $other = Farm::factory()->create();
        FarmMembership::factory()->create(['farm_id' => $other->id, 'user_id' => $owner->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        $this->actingAs($owner)->get(route('cultivation-cycles.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($owner)->withSession(['active_farm_id' => 999999])->get(route('cultivation-cycles.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($owner)->withSession(['active_farm_id' => $other->id])->get(route('cultivation-cycles.show', $cycle))->assertForbidden();
    }

    public function test_invalid_transitions_are_rejected_without_mutation(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        $service = app(CultivationCycleService::class);
        $service->start($cycle, $this->startPayload($farm));
        $this->expectExceptionMessage('current state');
        $service->start($cycle->fresh(), $this->startPayload($farm));
    }

    public function test_cancelled_cycle_cannot_be_started_or_cancelled_again(): void
    {
        [$owner, $farm, $pond, $cycle] = $this->fixture();
        app(CultivationCycleService::class)->cancel($cycle);
        try {
            app(CultivationCycleService::class)->cancel($cycle->fresh());
            $this->fail('Expected cancellation conflict.');
        } catch (\Throwable $e) {
            $this->assertSame('Only planned cycles can be cancelled.', $e->getMessage());
        }
        try {
            app(CultivationCycleService::class)->start($cycle->fresh(), $this->startPayload($farm));
            $this->fail('Expected start conflict.');
        } catch (\Throwable $e) {
            $this->assertSame('This cycle cannot be started in its current state.', $e->getMessage());
        }
        $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'status' => 'CANCELLED', 'initial_fish_count' => null]);
    }

    public function test_derived_lifecycle_has_deterministic_states_and_open_cycle_blocks_unavailable_update(): void
    {
        [$owner, $farm, $pond] = $this->pondFixture();
        $this->assertSame('EMPTY_AVAILABLE', $pond->derivedLifecycle());
        $pond->update(['operational_status' => PondOperationalStatus::MAINTENANCE]);
        $this->assertSame('MAINTENANCE', $pond->fresh()->derivedLifecycle());
        $pond->update(['operational_status' => PondOperationalStatus::INACTIVE]);
        $this->assertSame('INACTIVE', $pond->fresh()->derivedLifecycle());
        $pond->update(['operational_status' => PondOperationalStatus::AVAILABLE]);
        $species = FishSpecies::factory()->create();
        $cycle = app(CultivationCycleService::class)->createPlanned($owner, $farm, ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'L-01']);
        $this->assertSame('CYCLE_PLANNED', $pond->fresh()->derivedLifecycle());
        try {
            app(PondLifecycleService::class)->update($pond, ['operational_status' => 'MAINTENANCE']);
            $this->fail('Expected lifecycle conflict.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('cannot be made unavailable', $e->getMessage());
        }
        app(CultivationCycleService::class)->start($cycle, $this->startPayload($farm));
        $this->assertSame('ACTIVE_CULTIVATION', $pond->fresh()->derivedLifecycle());
    }

    public function test_pond_listing_preloads_cycles_for_bounded_lifecycle_queries(): void
    {
        [$owner, $farm] = $this->pondFixture();
        $species = FishSpecies::factory()->create(['is_active' => true]);
        $location = Location::factory()->create(['farm_id' => $farm->id]);
        $plannedPond = Pond::factory()->create(['farm_id' => $farm->id, 'location_id' => $location->id]);
        $activePond = Pond::factory()->create(['farm_id' => $farm->id, 'location_id' => $location->id]);
        app(CultivationCycleService::class)->createPlanned($owner, $farm, ['pond_id' => $plannedPond->id, 'fish_species_id' => $species->id, 'batch_code' => 'N-01']);
        $active = app(CultivationCycleService::class)->createPlanned($owner, $farm, ['pond_id' => $activePond->id, 'fish_species_id' => $species->id, 'batch_code' => 'N-02']);
        app(CultivationCycleService::class)->start($active, $this->startPayload($farm));

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->get(route('ponds.index'));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()->assertSee('CYCLE PLANNED')->assertSee('ACTIVE CULTIVATION');
        $this->assertLessThanOrEqual(11, count($queries), 'Pond lifecycle listing issued an unexpected per-row query count.');
    }

    private function fixture(): array
    {
        [$owner, $farm, $pond] = $this->pondFixture();
        $species = FishSpecies::factory()->create(['is_active' => true]);
        $cycle = app(CultivationCycleService::class)->createPlanned($owner, $farm, ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'T-01']);

        return [$owner, $farm, $pond, $cycle];
    }

    private function pondFixture(): array
    {
        $farm = Farm::factory()->create();
        $owner = $this->member($farm, FarmMembershipRole::FARM_OWNER);
        $location = Location::factory()->create(['farm_id' => $farm->id]);

        return [$owner, $farm, Pond::factory()->create(['farm_id' => $farm->id, 'location_id' => $location->id])];
    }

    private function member(Farm $farm, FarmMembershipRole $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function startPayload(?Farm $farm = null): array
    {
        $farm ??= Farm::firstOrFail();

        return ['stocked_at' => now()->subHour()->setTimezone($farm->timezone)->format('Y-m-d H:i'), 'initial_fish_count' => 1000, 'initial_average_weight_g' => '2.500'];
    }
}
