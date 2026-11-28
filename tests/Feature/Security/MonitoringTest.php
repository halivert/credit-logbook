<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Security\SecurityEvent;
use App\Security\SuspiciousActivityDetected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Suspicious events at a moment (UTC) from an IP.
     */
    private function events(string $at, string $type, int $count = 1, string $ip = '203.0.113.7', ?User $user = null): void
    {
        foreach (range(1, $count) as $i) {
            SecurityEvent::query()->forceCreate([
                'user_id' => $user?->id,
                'type' => $type,
                'ip_address' => $ip,
                'created_at' => Carbon::parse($at, 'UTC'),
            ]);
        }
    }

    private function alertEmail(): void
    {
        Notification::fake();
        config(['security.alerts.email' => 'admin@example.test']);
        $this->travelTo(Carbon::parse('2026-11-27 15:05', 'UTC'));
    }

    #[Test]
    public function an_ip_failing_on_many_emails_is_refused_whatever_the_email(): void
    {
        config(['security.login.ip_max_failures' => 5]);
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->post(route('login.store'), ['email' => "nadie{$i}@example.test", 'password' => 'x']);
        }

        // Even with the right password of a real account
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
        // Blocked attempts are recorded once a minute per IP
        $this->assertSame(1, SecurityEvent::where('type', SecurityEvent::IP_BLOCKED)->count());

        // Another IP is not affected
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_api_shares_the_limit_per_ip(): void
    {
        config(['security.login.ip_max_failures' => 3]);
        $user = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->postJson('/api/v1/login', ['email' => "nadie{$i}@example.test", 'password' => 'x'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertJsonValidationErrors('email');

        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(3, SecurityEvent::where('type', SecurityEvent::LOGIN_FAILED)->whereNull('user_id')->count());
    }

    #[Test]
    public function the_report_shows_each_day_and_the_ips_behind_them(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 12:00', 'America/Mexico_City'));
        $user = User::factory()->create();

        $this->events('2026-11-27 15:00', SecurityEvent::LOGIN_FAILED, 30, ip: '203.0.113.7');
        $this->events('2026-11-26 20:00', SecurityEvent::LOGIN_FAILED, 4, ip: '198.51.100.9', user: $user);
        $this->events('2026-11-26 20:05', SecurityEvent::LOCKOUT, 1, ip: '198.51.100.9', user: $user);
        // The same IP then got in
        $this->events('2026-11-26 20:30', SecurityEvent::LOGIN, 1, ip: '198.51.100.9', user: $user);
        // The previous 7 days
        $this->events('2026-11-18 20:00', SecurityEvent::LOGIN_FAILED, 10);

        $this->artisan('security:report')
            ->expectsTable(
                ['Día', 'Contraseña incorrecta', 'Correo sin cuenta', 'Código incorrecto', 'Bloqueos', 'IP bloqueada', 'Total'],
                [
                    ['2026-11-21', 0, 0, 0, 0, 0, 0],
                    ['2026-11-22', 0, 0, 0, 0, 0, 0],
                    ['2026-11-23', 0, 0, 0, 0, 0, 0],
                    ['2026-11-24', 0, 0, 0, 0, 0, 0],
                    ['2026-11-25', 0, 0, 0, 0, 0, 0],
                    ['2026-11-26', 4, 0, 0, 1, 0, 5],
                    ['2026-11-27', 0, 30, 0, 0, 0, 30],
                ],
            )
            ->expectsOutputToContain('35 (+250 % contra el periodo anterior)')
            ->expectsTable(
                ['IP', 'Intentos', 'Cuentas', 'Correos sin cuenta', 'Bloqueos', 'Entradas'],
                [
                    ['203.0.113.7', 30, 0, 30, 0, 0],
                    ['198.51.100.9', 5, 1, 0, 1, 1],
                ],
            )
            ->expectsOutputToContain('también entraron')
            ->assertSuccessful();
    }

    #[Test]
    public function an_unusual_hour_alerts_the_admin_with_the_ips(): void
    {
        $this->alertEmail();

        $this->events('2026-11-27 14:10', SecurityEvent::LOGIN_FAILED, 40);
        $this->events('2026-11-27 14:20', SecurityEvent::IP_BLOCKED, 3, ip: '198.51.100.9');

        $this->artisan('security:alert')->assertSuccessful();

        Notification::assertSentOnDemand(
            SuspiciousActivityDetected::class,
            function (SuspiciousActivityDetected $alert, array $channels, AnonymousNotifiable $notifiable) {
                $lines = implode("\n", $alert->toMail($notifiable)->introLines);

                return $notifiable->routes['mail'] === 'admin@example.test'
                    && str_contains($lines, '43 intentos sospechosos')
                    && str_contains($lines, 'IP 203.0.113.7: 40 intentos, 40 correos sin cuenta');
            },
        );
    }

    #[Test]
    public function a_normal_hour_does_not(): void
    {
        $this->alertEmail();

        $this->events('2026-11-27 14:10', SecurityEvent::LOGIN_FAILED, 5);
        $this->artisan('security:alert');

        Notification::assertNothingSent();
    }

    #[Test]
    public function a_busy_week_raises_the_bar(): void
    {
        $this->alertEmail();

        // 10 an hour on average the week before: 5 × 10 = 50 needed
        for ($hour = Carbon::parse('2026-11-20 14:00', 'UTC'); $hour->lt(Carbon::parse('2026-11-27 14:00', 'UTC')); $hour->addHour()) {
            $this->events($hour->toDateTimeString(), SecurityEvent::LOGIN_FAILED, 10);
        }

        $this->events('2026-11-27 14:10', SecurityEvent::LOGIN_FAILED, 40);
        $this->artisan('security:alert');
        Notification::assertNothingSent();

        $this->events('2026-11-27 14:15', SecurityEvent::LOGIN_FAILED, 10);
        $this->artisan('security:alert');
        Notification::assertSentOnDemandTimes(SuspiciousActivityDetected::class, 1);
    }

    #[Test]
    public function without_an_alert_email_nobody_is_alerted(): void
    {
        $this->alertEmail();
        config(['security.alerts.email' => null]);

        $this->events('2026-11-27 14:10', SecurityEvent::LOGIN_FAILED, 500);

        $this->artisan('security:alert')
            ->expectsOutputToContain('SECURITY_ALERT_EMAIL')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}
