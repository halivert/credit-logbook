<?php

namespace Tests\Feature\Invitations;

use App\Enums\Subscription;
use App\Invitations\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_command_creates_the_account_and_prints_the_link(): void
    {
        $this->artisan('user:invite', ['email' => 'Ana@Example.com', 'name' => 'Ana', '--founder' => true])
            ->expectsOutputToContain('/invitacion/')
            ->assertSuccessful();

        $user = User::query()->where('email', 'ana@example.com')->sole();

        $this->assertSame('Ana', $user->name);
        $this->assertSame('ana', $user->username);
        $this->assertSame(Subscription::Founder, $user->subscription);
        $this->assertNotNull($user->email_verified_at);
    }

    #[Test]
    public function invited_people_are_free_unless_founders(): void
    {
        $this->artisan('user:invite', ['email' => 'ana@example.com', 'name' => 'Ana'])->assertSuccessful();

        $this->assertSame(Subscription::Free, User::query()->sole()->subscription);
    }

    #[Test]
    public function usernames_dont_collide(): void
    {
        User::factory()->create(['username' => 'ana']);

        $this->artisan('user:invite', ['email' => 'ana@example.com', 'name' => 'Ana'])->assertSuccessful();

        $this->assertTrue(User::query()->where('username', 'ana2')->exists());
    }

    #[Test]
    public function an_existing_account_needs_again_for_a_new_link(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->artisan('user:invite', ['email' => 'ana@example.com', 'name' => 'Ana'])->assertFailed();

        $this->artisan('user:invite', ['email' => 'ana@example.com', 'name' => 'Ana', '--again' => true])
            ->expectsOutputToContain('/invitacion/'.$user->id)
            ->assertSuccessful();

        $this->assertSame(1, User::query()->count());
    }

    #[Test]
    public function an_invalid_email_is_rejected(): void
    {
        $this->artisan('user:invite', ['email' => 'no-es-correo', 'name' => 'Ana'])->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    #[Test]
    public function the_link_leads_to_choosing_a_password(): void
    {
        $user = User::factory()->create();

        $this->followingRedirects()
            ->get(Invitation::url($user))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('email', $user->email)
                ->where('invited', true));
    }

    #[Test]
    public function the_link_works_once(): void
    {
        $user = User::factory()->create();
        $url = Invitation::url($user);

        $token = Password::broker()->createToken($user);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'una-contraseña-larga-123',
            'password_confirmation' => 'una-contraseña-larga-123',
        ])->assertSessionHasNoErrors();

        $this->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');
    }

    #[Test]
    public function the_link_expires(): void
    {
        $user = User::factory()->create();
        $url = Invitation::url($user);

        $this->travel(Invitation::DAYS + 1)->days();

        $this->get($url)->assertForbidden();
    }

    #[Test]
    public function a_tampered_link_is_rejected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $url = str_replace($user->id, $other->id, Invitation::url($user));

        $this->get($url)->assertForbidden();
    }
}
