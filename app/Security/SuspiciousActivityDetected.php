<?php

namespace App\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Anonymous counts only: no users, emails or IPs.
 */
class SuspiciousActivityDetected extends Notification
{
    use Queueable;

    /**
     * @param  array<string, int>  $totals
     */
    public function __construct(
        public Carbon $hour,
        public array $totals,
        public float $baseline,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hour = $this->hour->copy()->setTimezone(config('security.timezone'));

        return (new MailMessage)
            ->subject(config('app.name').': actividad sospechosa')
            ->line(sprintf(
                'Entre las %s y las %s del %s hubo %d intentos sospechosos, contra un promedio de %s por hora la semana anterior.',
                $hour->format('H:i'),
                $hour->copy()->addHour()->format('H:i'),
                $hour->toDateString(),
                array_sum($this->totals),
                number_format($this->baseline, 1),
            ))
            ->line("Contraseña incorrecta: {$this->totals[SecurityMetric::LOGIN_FAILED]}")
            ->line("Correo sin cuenta: {$this->totals[SecurityMetric::LOGIN_FAILED_UNKNOWN]}")
            ->line("Código incorrecto: {$this->totals[SecurityMetric::TWO_FACTOR_FAILED]}")
            ->line("Bloqueos: {$this->totals[SecurityMetric::LOCKOUT]}")
            ->line('Muchos "correo sin cuenta" suelen ser alguien probando listas de contraseñas filtradas. Para ver la tendencia: php artisan security:report');
    }
}
