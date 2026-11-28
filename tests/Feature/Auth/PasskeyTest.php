<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passkeys\Passkey;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The server side of passkeys. The WebAuthn ceremonies themselves need a
 * browser authenticator, so they're checked end to end in a real browser.
 */
class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    private function passkeyFor(User $user, string $name = 'iPhone'): Passkey
    {
        return $user->passkeys()->forceCreate([
            'name' => $name,
            'credential_id' => bin2hex(random_bytes(16)),
            'credential' => ['publicKey' => 'not-a-real-key'],
        ]);
    }

    private function fakeCredential(): array
    {
        return [
            'id' => 'Zm9v',
            'rawId' => 'Zm9v',
            'type' => 'public-key',
            'response' => ['clientDataJSON' => 'e30', 'authenticatorData' => 'AA', 'signature' => 'AA'],
        ];
    }

    #[Test]
    public function guests_can_start_a_passkey_login(): void
    {
        $this->getJson(route('passkey.login-options'))
            ->assertOk()
            ->assertJsonPath('options.rpId', 'localhost')
            ->assertJsonStructure(['options' => ['challenge']]);
    }

    #[Test]
    public function an_unknown_passkey_does_not_sign_in(): void
    {
        $this->getJson(route('passkey.login-options'));

        $this->postJson(route('passkey.login'), ['credential' => $this->fakeCredential()])
            ->assertUnprocessable();

        $this->assertGuest();
    }

    #[Test]
    public function passkey_logins_are_rate_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->getJson(route('passkey.login-options'))->assertOk();
        }

        $this->getJson(route('passkey.login-options'))->assertTooManyRequests();
    }

    #[Test]
    public function adding_a_passkey_needs_the_password_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('passkey.registration-options'))
            ->assertStatus(423);

        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'password']);

        $this->actingAs($user)
            ->getJson(route('passkey.registration-options'))
            ->assertOk()
            ->assertJsonPath('options.rp.id', 'localhost')
            // The handle shown to authenticators doesn't reveal the user id
            ->assertJsonPath('options.user.id', fn (string $id) => ! str_contains(base64_decode(strtr($id, '-_', '+/')), $user->id));
    }

    #[Test]
    public function a_made_up_credential_is_not_stored(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'password']);
        $this->actingAs($user)->getJson(route('passkey.registration-options'));

        $this->actingAs($user)
            ->postJson(route('passkey.store'), ['name' => 'Falsa', 'credential' => $this->fakeCredential()])
            ->assertUnprocessable();

        $this->assertSame(0, $user->passkeys()->count());
    }

    #[Test]
    public function a_user_can_only_delete_their_own_passkeys(): void
    {
        $user = User::factory()->create();
        $theirs = $this->passkeyFor(User::factory()->create());
        $mine = $this->passkeyFor($user);

        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'password']);

        $this->actingAs($user)->delete(route('passkey.destroy', $theirs))->assertForbidden();
        $this->assertModelExists($theirs);

        $this->actingAs($user)
            ->from(route('profile.security'))
            ->delete(route('passkey.destroy', $mine))
            ->assertRedirect(route('profile.security'));
        $this->assertModelMissing($mine);
    }

    #[Test]
    public function the_security_page_lists_passkeys_without_their_credentials(): void
    {
        $user = User::factory()->create();
        $this->passkeyFor($user, 'MacBook');
        $this->passkeyFor(User::factory()->create(), 'Ajena');

        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'password']);

        $this->actingAs($user)
            ->get(route('profile.security'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('passkeys', 1)
                ->where('passkeys.0.name', 'MacBook')
                ->missing('passkeys.0.credential')
                ->missing('passkeys.0.credential_id')
            );
    }

    #[Test]
    public function deleting_the_account_deletes_its_passkeys(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkeyFor($user);

        $this->actingAs($user)->delete('/profile', ['password' => 'password']);

        $this->assertModelMissing($passkey);
    }
}
