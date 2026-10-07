<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_a_duplicate_email_without_creating_another_user(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.test']);

        $response = $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Another User',
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', $user->email)->count());
    }
}
