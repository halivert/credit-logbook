<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Http\Responses\PasswordResetLinkRequestResponse;
use App\Security\EnsureIpIsNotBlocked;
use Inertia\Inertia;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            FailedPasswordResetLinkRequestResponse::class,
            PasswordResetLinkRequestResponse::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        $this->views();

        // Fortify's sign in, with a limit per IP first
        Fortify::authenticateThrough(fn () => [
            EnsureIpIsNotBlocked::class,
            EnsureLoginIsNotThrottled::class,
            CanonicalizeUsername::class,
            RedirectsIfTwoFactorAuthenticatable::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower(
                    $request->input(Fortify::username())
                ) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        // Shared by every passkey route. Opening the login page already asks
        // for options (the browser's autofill), so it can't be too tight;
        // signed in, each person has their own budget.
        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id')
            );
        });
    }

    /**
     * Fortify handles every authentication route; these are its pages.
     */
    private function views(): void
    {
        Fortify::loginView(fn () => Inertia::render('Auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => session('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('Auth/TwoFactorChallenge'));

        Fortify::registerView(fn () => Inertia::render('Auth/Register'));

        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/ResetPassword', [
            'email' => $request->string('email')->toString(),
            'token' => $request->route('token'),
        ]));

        Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('Auth/ConfirmPassword'));
    }
}
