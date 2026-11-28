<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Security\SecurityMetric;
use App\Security\SuspiciousActivityDetected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function failLogin(string $email): void
    {
        $this->post(route('login.store'), ['email' => $email, 'password' => 'wrong']);
    }

    /**
     * Suspicious events in the hour starting at $hour (UTC).
     */
    private function metric(string $hour, string $type, int $total): void
    {
        SecurityMetric::query()->create(['hour' => Carbon::parse($hour, 'UTC'), 'type' => $type, 'total' => $total]);
    }

    #[Test]
    public function suspicious_events_are_counted_without_saying_whose(): void
    {
        $user = User::factory()->create();

        $this->failLogin($user->email);
        $this->failLogin($user->email);
        $this->failLogin('nadie@example.test');

        $this->assertSame(2, SecurityMetric::where('type', SecurityMetric::LOGIN_FAILED)->value('total'));
        $this->assertSame(1, SecurityMetric::where('type', SecurityMetric::LOGIN_FAILED_UNKNOWN)->value('total'));

        // Nothing that points to a person
        $this->assertSame(['id', 'hour', 'type', 'total'], Schema::getColumnListing('security_metrics'));
    }

    #[Test]
    public function the_report_shows_each_day_and_compares_with_the_previous_period(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 12:00', 'America/Mexico_City'));

        $this->metric('2026-11-27 15:00', SecurityMetric::LOGIN_FAILED_UNKNOWN, 30);
        $this->metric('2026-11-26 20:00', SecurityMetric::LOGIN_FAILED, 4);
        $this->metric('2026-11-26 20:00', SecurityMetric::LOCKOUT, 1);
        // The previous 7 days
        $this->metric('2026-11-18 20:00', SecurityMetric::LOGIN_FAILED, 10);

        $this->artisan('security:report')
            ->expectsTable(
                ['Día', 'Contraseña incorrecta', 'Correo sin cuenta', 'Código incorrecto', 'Bloqueos', 'Total'],
                [
                    ['2026-11-21', 0, 0, 0, 0, 0],
                    ['2026-11-22', 0, 0, 0, 0, 0],
                    ['2026-11-23', 0, 0, 0, 0, 0],
                    ['2026-11-24', 0, 0, 0, 0, 0],
                    ['2026-11-25', 0, 0, 0, 0, 0],
                    ['2026-11-26', 4, 0, 0, 1, 5],
                    ['2026-11-27', 0, 30, 0, 0, 30],
                ],
            )
            ->expectsOutputToContain('35 (+250 % contra el periodo anterior)')
            ->assertSuccessful();
    }

    #[Test]
    public function an_unusual_hour_sends_an_alert(): void
    {
        Notification::fake();
        config(['security.alerts.email' => 'admin@example.test']);
        $this->travelTo(Carbon::parse('2026-11-27 15:05', 'UTC'));

        $this->metric('2026-11-27 14:00', SecurityMetric::LOGIN_FAILED_UNKNOWN, 40);
        $this->metric('2026-11-27 14:00', SecurityMetric::LOCKOUT, 3);

        $this->artisan('security:alert')->assertSuccessful();

        Notification::assertSentOnDemand(
            SuspiciousActivityDetected::class,
            fn (SuspiciousActivityDetected $alert, array $channels, AnonymousNotifiable $notifiable) =>
                $notifiable->routes['mail'] === 'admin@example.test'
                && $alert->totals[SecurityMetric::LOGIN_FAILED_UNKNOWN] === 40
                && str_contains(implode(' ', $alert->toMail($notifiable)->introLines), '43 intentos sospechosos'),
        );
    }

    #[Test]
    public function a_normal_hour_does_not(): void
    {
        Notification::fake();
        config(['security.alerts.email' => 'admin@example.test']);
        $this->travelTo(Carbon::parse('2026-11-27 15:05', 'UTC'));

        // Under the minimum
        $this->metric('2026-11-27 14:00', SecurityMetric::LOGIN_FAILED, 5);
        $this->artisan('security:alert');

        Notification::assertNothingSent();
    }

    #[Test]
    public function a_busy_week_raises_the_bar(): void
    {
        Notification::fake();
        config(['security.alerts.email' => 'admin@example.test']);
        $this->travelTo(Carbon::parse('2026-11-27 15:05', 'UTC'));

        // 10 an hour on average the week before: 5 × 10 = 50 needed
        for ($hour = Carbon::parse('2026-11-20 14:00', 'UTC'); $hour->lt(Carbon::parse('2026-11-27 14:00', 'UTC')); $hour->addHour()) {
            SecurityMetric::query()->create(['hour' => $hour->copy(), 'type' => SecurityMetric::LOGIN_FAILED, 'total' => 10]);
        }

        $this->metric('2026-11-27 14:00', SecurityMetric::LOGIN_FAILED, 40);
        $this->artisan('security:alert');
        Notification::assertNothingSent();

        SecurityMetric::query()->where('hour', Carbon::parse('2026-11-27 14:00', 'UTC'))->update(['total' => 50]);
        $this->artisan('security:alert');
        Notification::assertSentOnDemandTimes(SuspiciousActivityDetected::class, 1);
    }

    #[Test]
    public function without_an_alert_email_nobody_is_alerted(): void
    {
        Notification::fake();
        config(['security.alerts.email' => null]);
        $this->travelTo(Carbon::parse('2026-11-27 15:05', 'UTC'));

        $this->metric('2026-11-27 14:00', SecurityMetric::LOGIN_FAILED_UNKNOWN, 500);

        $this->artisan('security:alert')
            ->expectsOutputToContain('SECURITY_ALERT_EMAIL')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    #[Test]
    public function counts_are_kept_for_a_year(): void
    {
        $this->metric(now()->subDays(366)->toDateTimeString(), SecurityMetric::LOGIN_FAILED, 1);
        $this->metric(now()->subDays(300)->toDateTimeString(), SecurityMetric::LOGIN_FAILED, 1);

        $this->artisan('model:prune', ['--model' => [SecurityMetric::class]]);

        $this->assertSame(1, SecurityMetric::count());
    }
}
