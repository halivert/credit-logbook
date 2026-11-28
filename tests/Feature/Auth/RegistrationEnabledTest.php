<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationEnabledTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Routes are registered at boot depending on REGISTRATION, so it has to
     * be set before the application is created.
     */
    protected function setUp(): void
    {
        $_ENV['REGISTRATION'] = $_SERVER['REGISTRATION'] = 'true';
        putenv('REGISTRATION=true');

        // With a local .env the shared env repository remembers it loaded
        // REGISTRATION and would overwrite it again on boot. A fresh one
        // respects the value above.
        Env::enablePutenv();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['REGISTRATION'], $_SERVER['REGISTRATION']);
        putenv('REGISTRATION');
        Env::enablePutenv();
    }

    #[Test]
    public function new_users_can_register(): void
    {
        $response = $this->post('/registro', [
            'name' => 'Test User',
            'username' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
