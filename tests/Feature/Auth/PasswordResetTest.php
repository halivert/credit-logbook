<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors();

            return true;
        });
    }

    public function test_unknown_emails_get_the_same_answer_as_known_ones(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $known = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nadie@example.test']);

        $known->assertSessionHasNoErrors()->assertRedirect(route('password.request'));
        $unknown->assertSessionHasNoErrors()->assertRedirect(route('password.request'));
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        Notification::assertCount(1);
    }

    public function test_the_reset_email_goes_through_the_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        // The page doesn't wait for the mail server: the worker sends it
        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job) => $job->notification instanceof ResetPassword,
        );
    }

    private function requestResets(User $user, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->post(route('password.email'), ['email' => $user->email]);
            // Past the broker's own throttle (one token per minute)
            $this->travel(61)->seconds();
        }
    }

    public function test_an_account_gets_three_reset_emails_an_hour_at_most(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->requestResets($user, 5);
        Notification::assertSentToTimes($user, ResetPassword::class, 3);

        $this->travel(1)->hour();
        $this->requestResets($user, 1);
        Notification::assertSentToTimes($user, ResetPassword::class, 4);
    }

    public function test_reset_emails_have_a_daily_cap_for_everyone(): void
    {
        Notification::fake();
        config(['auth.reset_emails.per_day' => 2]);

        foreach (User::factory()->count(3)->create() as $user) {
            $this->requestResets($user, 1);
        }

        Notification::assertCount(2);
    }

    public function test_over_the_limit_the_page_answers_the_same(): void
    {
        Notification::fake();
        config(['auth.reset_emails.per_account_per_hour' => 1]);

        $user = User::factory()->create();
        $this->requestResets($user, 1);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }
}
