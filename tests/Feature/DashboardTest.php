<?php

namespace Tests\Feature;

use App\Enums\FarmMembershipRole;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_multiple_memberships_without_active_farm_renders_selection_state(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $first = Farm::factory()->create(['name' => 'First Farm']);
        $second = Farm::factory()->create(['name' => 'Second Farm']);
        FarmMembership::factory()->create(['farm_id' => $first->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        FarmMembership::factory()->create(['farm_id' => $second->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertSee('Choose a farm to continue')->assertSee('First Farm')->assertSee('Second Farm')->assertDontSee('Farm workspace');
    }

    public function test_one_membership_is_auto_selected_and_rendered(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $farm = Farm::factory()->create(['name' => 'Only Farm']);
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertSee('Only Farm')->assertSee('Farm workspace');
    }

    public function test_two_memberships_with_valid_active_farm_render_active_state(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $active = Farm::factory()->create(['name' => 'Active Farm']);
        $other = Farm::factory()->create(['name' => 'Other Farm']);
        FarmMembership::factory()->create(['farm_id' => $active->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        FarmMembership::factory()->create(['farm_id' => $other->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::POND_OPERATOR]);
        session()->put('active_farm_id', $active->id);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Active Farm')->assertSee('Farm workspace');
    }

    public function test_selection_only_contains_member_farms_and_post_selection_renders_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $memberFarm = Farm::factory()->create(['name' => 'Member Farm']);
        $outsiderFarm = Farm::factory()->create(['name' => 'Outsider Farm']);
        FarmMembership::factory()->create(['farm_id' => $memberFarm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Member Farm')->assertDontSee('Outsider Farm');
        $this->post(route('farms.select', $memberFarm))->assertRedirect();
        $this->get(route('dashboard'))->assertOk()->assertSee('Member Farm')->assertSee('Farm workspace');
    }
}
