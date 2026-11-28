<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What people read outside the pages is in Spanish too.
 */
class SpanishTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_password_reset_email_is_in_spanish(): void
    {
        $mail = (new ResetPassword('token'))->toMail(User::factory()->create());

        $this->assertSame('Restablece tu contraseña', $mail->subject);
        $this->assertSame('Restablecer contraseña', $mail->actionText);
        $this->assertStringContainsString('Saludos', (string) $mail->render());
    }

    #[Test]
    public function the_email_verification_email_is_in_spanish(): void
    {
        $mail = (new VerifyEmail)->toMail(User::factory()->create());

        $this->assertSame('Verifica tu correo', $mail->subject);
        $this->assertSame('Verificar correo', $mail->actionText);
    }

    #[Test]
    public function error_pages_are_in_spanish(): void
    {
        $this->get('/no-existe')->assertNotFound()->assertSee('No encontrado');
    }
}
