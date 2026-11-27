<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['REGISTRATION'], $_SERVER['REGISTRATION']);
    }

    #[Test]
    public function registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    #[Test]
    public function new_users_can_register(): void
    {
        $response = $this->post('/register', [
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
