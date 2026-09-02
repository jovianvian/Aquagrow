<?php

namespace Tests\Feature;

use App\Enums\FarmMembershipRole;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocationPondManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_locations(): void
    {
        $this->get(route('locations.index'))->assertRedirect(route('login'));
    }

    public function test_owner_can_create_location_and_pond_and_dashboard_shows_real_summary(): void
    {
        [$user, $farm] = $this->member(FarmMembershipRole::FARM_OWNER);
        $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])
            ->post(route('locations.store'), ['name' => 'North Site', 'address' => 'River Road'])
            ->assertRedirect();
        $location = $farm->locations()->first();

        $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])
            ->post(route('ponds.store'), ['location_id' => $location->id, 'name' => 'Pond One', 'code' => 'p-01', 'type' => 'EARTHEN', 'operational_status' => 'AVAILABLE', 'length_m' => '10.50', 'capacity_fish' => 1000])
            ->assertRedirect();

        $response = $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])->get(route('dashboard'));
        $response->assertOk()->assertSee('1 location')->assertSee('1 pond')->assertSee('Available');
    }

    public function test_operator_is_read_only_and_non_member_is_denied(): void
    {
        [$operator, $farm] = $this->member(FarmMembershipRole::POND_OPERATOR);
        $location = $farm->locations()->create(['name' => 'Site', 'slug' => 'site']);
        $this->actingAs($operator)->withSession(['active_farm_id' => $farm->id])->get(route('locations.index'))->assertOk();
        $this->actingAs($operator)->withSession(['active_farm_id' => $farm->id])->post(route('locations.store'), ['name' => 'Nope'])->assertForbidden();
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($outsider)->withSession(['active_farm_id' => $farm->id])->get(route('locations.show', $location))->assertForbidden();
    }

    public function test_pond_rejects_cross_farm_location_and_invalid_values(): void
    {
        [$user, $farm] = $this->member(FarmMembershipRole::FARM_OWNER);
        $other = Farm::factory()->create();
        $location = $other->locations()->create(['name' => 'Other Site', 'slug' => 'other-site']);
        $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])->post(route('ponds.store'), ['location_id' => $location->id, 'name' => 'Pond', 'code' => 'bad code/', 'type' => 'INVALID', 'operational_status' => 'INVALID', 'length_m' => 0])->assertSessionHasErrors(['location_id', 'code', 'type', 'operational_status', 'length_m']);
        $this->assertDatabaseCount('ponds', 0);
    }

    public function test_database_composite_foreign_key_rejects_cross_farm_pond(): void
    {
        [$user, $farm] = $this->member(FarmMembershipRole::FARM_OWNER);
        $other = Farm::factory()->create();
        $location = $other->locations()->create(['name' => 'Other Site', 'slug' => 'other-site']);
        $this->expectException(QueryException::class);
        $farm->ponds()->create(['location_id' => $location->id, 'name' => 'Pond', 'code' => 'P-01', 'type' => 'EARTHEN', 'operational_status' => 'AVAILABLE']);
    }

    public function test_location_workspace_requires_an_active_farm(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->get(route('locations.index'))->assertRedirect(route('dashboard'));

        [$user, $farm] = $this->member(FarmMembershipRole::FARM_OWNER);
        $other = Farm::factory()->create();
        FarmMembership::factory()->create(['farm_id' => $other->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        $this->actingAs($user)->withSession(['active_farm_id' => 999999])->get(route('locations.index'))->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_update_but_operator_cannot_update_location_or_pond(): void
    {
        [$owner, $farm] = $this->member(FarmMembershipRole::FARM_OWNER);
        $location = $farm->locations()->create(['name' => 'Site', 'slug' => 'site']);
        $pond = $farm->ponds()->create(['location_id' => $location->id, 'name' => 'Pond', 'code' => 'P-01', 'type' => 'EARTHEN', 'operational_status' => 'AVAILABLE']);
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->put(route('locations.update', $location), ['name' => 'Updated'])->assertRedirect();
        $this->actingAs($owner)->withSession(['active_farm_id' => $farm->id])->put(route('ponds.update', $pond), ['location_id' => $location->id, 'name' => 'Updated Pond', 'code' => 'P-01', 'type' => 'EARTHEN', 'operational_status' => 'AVAILABLE'])->assertRedirect();
        $operator = User::factory()->create(['email_verified_at' => now()]);
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $operator->id, 'role' => FarmMembershipRole::POND_OPERATOR]);
        $this->actingAs($operator)->withSession(['active_farm_id' => $farm->id])->put(route('locations.update', $location), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($operator)->withSession(['active_farm_id' => $farm->id])->put(route('ponds.update', $pond), ['location_id' => $location->id, 'name' => 'Nope', 'code' => 'P-01', 'type' => 'EARTHEN', 'operational_status' => 'AVAILABLE'])->assertForbidden();
    }

    public function test_duplicate_code_is_rejected_and_nullable_measurements_remain_null(): void
    {
        [$user, $farm] = $this->member(FarmMembershipRole::FARM_OWNER);
        $location = $farm->locations()->create(['name' => 'Site', 'slug' => 'site']);
        $payload = ['location_id' => $location->id, 'name' => 'Pond', 'code' => 'p-01', 'type' => 'OTHER', 'operational_status' => 'INACTIVE'];
        $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])->post(route('ponds.store'), $payload)->assertRedirect();
        $this->actingAs($user)->withSession(['active_farm_id' => $farm->id])->post(route('ponds.store'), [...$payload, 'name' => 'Pond Two'])->assertSessionHasErrors('code');
        $pond = $farm->ponds()->first();
        $this->assertNull($pond->length_m);
        $this->assertNull($pond->water_volume_l);
    }

    public function test_database_check_constraints_reject_invalid_pond_status_and_measurement(): void
    {
        [$user, $farm] = $this->member(FarmMembershipRole::FARM_OWNER);
        $location = $farm->locations()->create(['name' => 'Site', 'slug' => 'site']);
        $this->expectException(QueryException::class);
        DB::table('ponds')->insert(['farm_id' => $farm->id, 'location_id' => $location->id, 'name' => 'Pond', 'code' => 'P-01', 'type' => 'INVALID', 'operational_status' => 'INVALID', 'length_m' => -1]);
    }

    private function member(FarmMembershipRole $role): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $farm = Farm::factory()->create();
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user, $farm];
    }
}
