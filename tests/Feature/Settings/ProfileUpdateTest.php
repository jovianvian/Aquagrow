<?php

namespace Tests\Feature\Settings;

use App\Enums\FarmMembershipRole;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }

    public function test_sole_owner_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();
        $farm = Farm::factory()->create();
        FarmMembership::factory()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'role' => FarmMembershipRole::FARM_OWNER,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
        $this->assertDatabaseHas('farm_memberships', ['farm_id' => $farm->id, 'user_id' => $user->id]);
    }

    public function test_account_deletion_is_atomic_when_another_owner_exists(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create();
        $farm = Farm::factory()->create();
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $otherOwner->id, 'role' => FarmMembershipRole::FARM_OWNER]);

        app(AccountDeletionService::class)->delete($user);

        $this->assertNull($user->fresh());
        $this->assertSame(1, FarmMembership::query()->where('farm_id', $farm->id)->where('role', FarmMembershipRole::FARM_OWNER)->count());
        $this->assertDatabaseHas('farm_memberships', ['farm_id' => $farm->id, 'user_id' => $otherOwner->id]);
    }
}
