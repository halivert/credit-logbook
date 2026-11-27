<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Registration is disabled unless REGISTRATION=true.
 *
 * @see RegistrationEnabledTest
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registration_is_closed_by_default(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}
