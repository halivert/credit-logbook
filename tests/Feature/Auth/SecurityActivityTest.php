<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Security\SecurityEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Passkey;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityActivityTest extends TestCase
{
    use RefreshDatabase;

    private const MAC_CHROME = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    private function events(User $user): array
    {
        return $user->securityEvents()->oldest()->get()
            ->map(fn (SecurityEvent $event) => array_values(array_filter([$event->type, $event->method, $event->detail])))
            ->all();
    }

    private function logIn(User $user, string $password = 'password'): void
    {
        $this->withHeader('User-Agent', self::MAC_CHROME)
            ->post(route('login.store'), ['email' => $user->email, 'password' => $password]);
    }

    private function withTwoFactor(User $user): string
    {
        $secret = (new Google2FA)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-one'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    #[Test]
    public function a_password_sign_in_is_recorded_with_where_it_came_from(): void
    {
        $user = User::factory()->create();

        $this->logIn($user);

        $event = $user->securityEvents()->sole();

        $this->assertSame([SecurityEvent::LOGIN, 'password'], [$event->type, $event->method]);
        $this->assertSame('127.0.0.1', $event->ip_address);
        $this->assertSame(self::MAC_CHROME, $event->user_agent);
    }

    #[Test]
    public function wrong_passwords_are_recorded_for_the_account_but_unknown_emails_are_not(): void
    {
        $user = User::factory()->create();

        $this->logIn($user, 'wrong');
        $this->post(route('login.store'), ['email' => 'nadie@example.test', 'password' => 'x']);

        $this->assertSame([[SecurityEvent::LOGIN_FAILED]], $this->events($user));
        $this->assertDatabaseCount('security_events', 1);
    }

    #[Test]
    public function too_many_attempts_are_recorded(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->logIn($user, 'wrong');
        }

        // Even the right password waits, and the form says why
        $this->withHeader('User-Agent', self::MAC_CHROME)
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertContains([SecurityEvent::LOCKOUT], $this->events($user));
    }

    #[Test]
    public function two_factor_sign_ins_say_how_they_were_completed(): void
    {
        $user = User::factory()->create();
        $secret = $this->withTwoFactor($user);

        // The password alone isn't a sign in yet
        $this->logIn($user);
        $this->assertSame([], $this->events($user));

        $this->post(route('two-factor.login.store'), ['code' => '000000']);
        $this->post(route('two-factor.login.store'), ['code' => (new Google2FA)->getCurrentOtp($secret)]);
        $this->post(route('logout'));

        $this->logIn($user);
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-one']);

        $this->assertSame([
            [SecurityEvent::TWO_FACTOR_FAILED],
            [SecurityEvent::LOGIN, 'two_factor'],
            [SecurityEvent::LOGIN, 'recovery_code'],
        ], $this->events($user));
    }

    #[Test]
    public function changes_to_how_the_account_signs_in_are_recorded(): void
    {
        $user = User::factory()->create();
        $passkey = $user->passkeys()->forceCreate([
            'name' => 'iPhone',
            'credential_id' => 'credential',
            'credential' => ['publicKey' => 'key'],
        ]);

        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'password']);

        $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ]);
        $this->actingAs($user)->post(route('two-factor.enable'));
        $this->actingAs($user)->post(route('two-factor.confirm'), [
            'code' => (new Google2FA)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret)),
        ]);
        $this->actingAs($user)->post(route('two-factor.regenerate-recovery-codes'));
        $this->actingAs($user)->delete(route('two-factor.disable'));
        PasskeyRegistered::dispatch($user, $passkey);
        $this->actingAs($user)->delete(route('passkey.destroy', $passkey));

        $this->assertSame([
            [SecurityEvent::PASSWORD_CHANGED],
            [SecurityEvent::TWO_FACTOR_ENABLED],
            [SecurityEvent::RECOVERY_CODES_GENERATED],
            [SecurityEvent::TWO_FACTOR_DISABLED],
            [SecurityEvent::PASSKEY_ADDED, 'iPhone'],
            [SecurityEvent::PASSKEY_REMOVED, 'iPhone'],
        ], $this->events($user));
    }

    #[Test]
    public function the_security_page_shows_only_your_own_activity(): void
    {
        $user = User::factory()->create();
        $intruder = User::factory()->create();

        $this->logIn($user, 'wrong');
        $this->logIn($intruder);

        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'password']);

        $this->actingAs($user)
            ->get(route('profile.security'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events', 1)
                ->where('events.0.type', SecurityEvent::LOGIN_FAILED)
                ->where('events.0.suspicious', true)
                ->where('events.0.device', 'Chrome en macOS')
                ->where('events.0.ip', '127.0.0.1')
                ->missing('events.0.user_agent')
            );
    }

    #[Test]
    public function activity_older_than_90_days_is_pruned(): void
    {
        $user = User::factory()->create();

        $this->travel(-91)->days();
        $this->logIn($user);
        $this->travelBack();
        $this->post(route('logout'));
        $this->logIn($user);

        $this->artisan('model:prune', ['--model' => [SecurityEvent::class]])->assertSuccessful();

        $this->assertSame(1, $user->securityEvents()->count());
    }

    #[Test]
    public function deleting_the_account_deletes_its_activity(): void
    {
        $user = User::factory()->create();
        $this->logIn($user);

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

        $this->assertDatabaseCount('security_events', 0);
    }
}
