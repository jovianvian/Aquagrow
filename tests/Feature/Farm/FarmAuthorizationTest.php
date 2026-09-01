<?php

namespace Tests\Feature\Farm;

use App\Enums\FarmMembershipRole;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\User;
use App\Services\ActiveFarmContext;
use App\Services\FarmMembershipService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FarmAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_a_farm(): void
    {
        $this->post(route('farms.store'), [])->assertRedirect(route('login'));
    }

    public function test_user_can_create_farm_with_owner_membership_and_active_context(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->post(route('farms.store'), ['name' => 'Blue Water Farm', 'timezone' => 'Asia/Jakarta', 'currency' => 'IDR'])->assertRedirect(route('dashboard'));
        $farm = Farm::first();
        $this->assertNotNull($farm);
        $this->assertSame('blue-water-farm', $farm->slug);
        $this->assertDatabaseHas('farm_memberships', ['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER->value]);
        $this->assertSame($farm->id, session('active_farm_id'));
    }

    public function test_invalid_timezone_and_currency_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))->post(route('farms.store'), ['name' => 'Farm', 'timezone' => 'not/a-timezone', 'currency' => 'USD'])->assertSessionHasErrors(['timezone', 'currency']);
        $this->assertDatabaseCount('farms', 0);
    }

    public function test_member_can_view_but_non_member_cannot_select_or_update_another_farm(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $farm = Farm::factory()->create();
        FarmMembership::create(['farm_id' => $farm->id, 'user_id' => $owner->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        FarmMembership::create(['farm_id' => $farm->id, 'user_id' => $member->id, 'role' => FarmMembershipRole::POND_OPERATOR]);
        $this->actingAs($member)->post(route('farms.select', $farm))->assertRedirect();
        $this->assertSame($farm->id, session('active_farm_id'));
        $this->actingAs($outsider)->post(route('farms.select', $farm))->assertForbidden();
        $this->assertFalse($outsider->can('view', $farm));
        $this->assertFalse($member->can('update', $farm));
        $this->assertTrue($owner->can('update', $farm));
    }

    public function test_membership_pair_and_role_constraint_are_enforced(): void
    {
        $user = User::factory()->create();
        $farm = Farm::factory()->create();
        FarmMembership::create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        $this->expectException(UniqueConstraintViolationException::class);
        FarmMembership::create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::POND_OPERATOR]);
    }

    public function test_slug_collision_gets_a_unique_suffix(): void
    {
        Farm::factory()->create(['slug' => 'blue-water-farm']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->post(route('farms.store'), ['name' => 'Blue Water Farm', 'timezone' => 'Asia/Jakarta', 'currency' => 'IDR'])->assertRedirect();
        $this->assertDatabaseHas('farms', ['slug' => 'blue-water-farm-2']);
    }

    public function test_last_owner_cannot_be_demoted(): void
    {
        $farm = Farm::factory()->create();
        $user = User::factory()->create();
        $membership = FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        $this->expectException(\DomainException::class);
        app(FarmMembershipService::class)->changeRole($membership, FarmMembershipRole::POND_OPERATOR);
    }

    public function test_last_owner_cannot_be_removed(): void
    {
        $farm = Farm::factory()->create();
        $user = User::factory()->create();
        $membership = FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        $this->expectException(\DomainException::class);
        app(FarmMembershipService::class)->remove($membership);
    }

    public function test_membership_mutation_waits_for_the_canonical_farm_lock(): void
    {
        $farm = Farm::factory()->create();
        $user = User::factory()->create();
        $membership = FarmMembership::factory()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'role' => FarmMembershipRole::FARM_OWNER,
        ]);

        config(['database.connections.farm_lock_probe' => config('database.connections.pgsql')]);
        $lockConnection = DB::connection('farm_lock_probe');
        $serviceConnection = DB::connection();
        $serviceConnection->statement('SET lock_timeout TO \'250ms\'');
        $lockConnection->beginTransaction();
        $lockConnection->table('farms')->where('id', $farm->id)->lockForUpdate()->first();

        try {
            $this->expectException(\Throwable::class);
            app(FarmMembershipService::class)->changeRole($membership, FarmMembershipRole::POND_OPERATOR);
        } finally {
            if ($lockConnection->transactionLevel() > 0) {
                $lockConnection->rollBack();
            }
            $serviceConnection->statement('SET lock_timeout TO DEFAULT');
            DB::purge('farm_lock_probe');
        }
    }

    public function test_stale_session_with_multiple_farms_requires_explicit_selection(): void
    {
        $user = User::factory()->create();
        $first = Farm::factory()->create();
        $second = Farm::factory()->create();
        FarmMembership::factory()->create(['farm_id' => $first->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        FarmMembership::factory()->create(['farm_id' => $second->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        session()->put('active_farm_id', 999999);
        $this->assertNull(app(ActiveFarmContext::class)->current($user));
        $this->assertNull(session('active_farm_id'));
    }
}
