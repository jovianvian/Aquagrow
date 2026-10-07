<?php

namespace Tests\Feature;

use App\Enums\CultivationCycleStatus;
use App\Enums\FarmMembershipRole;
use App\Enums\PondOperationalStatus;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\FishSpecies;
use App\Models\Location;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CultivationCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_species_are_seeded_idempotently_and_active_species_are_available(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('fish_species', 3);
        $this->assertSame(['GURAME', 'LELE', 'NILA'], FishSpecies::query()->orderBy('code')->pluck('code')->all());
    }

    public function test_owner_can_create_and_start_cycle_but_operator_cannot(): void
    {
        [$owner, $farm, $pond] = $this->pondContext(FarmMembershipRole::FARM_OWNER);
        $species = FishSpecies::factory()->create(['code' => 'NILA', 'is_active' => true]);
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->post(route('cultivation-cycles.store'), ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'batch-01'])->assertRedirect();
        $cycle = $farm->cultivationCycles()->first();
        $this->assertSame(CultivationCycleStatus::PLANNED, $cycle->status);
        $this->actingAs($owner)->post(route('cultivation-cycles.start', $cycle), ['stocked_at' => now()->subHour()->setTimezone($farm->timezone)->format('Y-m-d H:i'), 'initial_fish_count' => 1000, 'initial_average_weight_g' => '2.50'])->assertRedirect();
        $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'status' => 'ACTIVE', 'initial_fish_count' => 1000]);
        $operator = User::factory()->create(['email_verified_at' => now()]);
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $operator->id, 'role' => FarmMembershipRole::POND_OPERATOR]);
        $this->actingAs($operator)->withSession(['active_farm_id' => $farm->id])->post(route('cultivation-cycles.store'), ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'B-02'])->assertForbidden();
    }

    public function test_only_one_open_cycle_is_allowed_and_cancel_preserves_history(): void
    {
        [$owner, $farm, $pond] = $this->pondContext(FarmMembershipRole::FARM_OWNER);
        $species = FishSpecies::factory()->create(['is_active' => true]);
        $payload = ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'B-01'];
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->post(route('cultivation-cycles.store'), $payload)->assertRedirect();
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->post(route('cultivation-cycles.store'), [...$payload, 'batch_code' => 'B-02'])->assertSessionHasErrors('pond_id');
        $cycle = $farm->cultivationCycles()->first();
        $this->actingAs($owner)->post(route('cultivation-cycles.cancel', $cycle), [])->assertRedirect();
        $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'status' => 'CANCELLED']);
    }

    public function test_maintenance_pond_cannot_start_cycle_and_inactive_species_cannot_be_selected(): void
    {
        [$owner, $farm, $pond] = $this->pondContext(FarmMembershipRole::FARM_OWNER, PondOperationalStatus::MAINTENANCE);
        $species = FishSpecies::factory()->create(['is_active' => false]);
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->post(route('cultivation-cycles.store'), ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'B-01'])->assertSessionHasErrors('fish_species_id');
    }

    public function test_composite_cycle_foreign_key_rejects_cross_farm_pond(): void
    {
        [$owner, $farm] = $this->farmContext();
        $other = Farm::factory()->create();
        $location = Location::factory()->create(['farm_id' => $other->id]);
        $pond = Pond::factory()->create(['farm_id' => $other->id, 'location_id' => $location->id]);
        $species = FishSpecies::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('cultivation_cycles')->insert(['farm_id' => $farm->id, 'pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'B-01', 'status' => 'PLANNED', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function farmContext(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $farm = Farm::factory()->create();
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);

        return [$user, $farm];
    }

    private function pondContext(FarmMembershipRole $role, PondOperationalStatus $status = PondOperationalStatus::AVAILABLE): array
    {
        [$user, $farm] = $this->farmContext();
        $location = Location::factory()->create(['farm_id' => $farm->id]);
        $pond = Pond::factory()->create(['farm_id' => $farm->id, 'location_id' => $location->id, 'operational_status' => $status]);
        if ($role !== FarmMembershipRole::FARM_OWNER) {
            FarmMembership::query()->where(['farm_id' => $farm->id, 'user_id' => $user->id])->update(['role' => $role->value]);
        }

        return [$user, $farm, $pond];
    }
}
