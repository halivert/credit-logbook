<?php

namespace App\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * For the admin: counts and the IPs behind them, never which accounts.
 */
class SuspiciousActivityDetected extends Notification
{
    use Queueable;

    /**
     * @param  array<string, int>  $totals
     * @param  list<array{ip: string, attempts: int, accounts: int, unknown: int, lockouts: int, sign_ins: int}>  $ips
     */
    public function __construct(
        public Carbon $hour,
        public array $totals,
        public float $baseline,
        public array $ips,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hour = $this->hour->copy()->setTimezone(config('security.timezone'));

        $mail = (new MailMessage)
            ->subject(config('app.name').': actividad sospechosa')
            ->line(sprintf(
                'Entre las %s y las %s del %s hubo %d intentos sospechosos, contra un promedio de %s por hora la semana anterior.',
                $hour->format('H:i'),
                $hour->copy()->addHour()->format('H:i'),
                $hour->toDateString(),
                array_sum($this->totals),
                number_format($this->baseline, 1),
            ));

        foreach (SuspiciousActivity::LABELS as $category => $label) {
            $mail->line("{$label}: {$this->totals[$category]}");
        }

        foreach ($this->ips as $ip) {
            $mail->line(sprintf(
                'IP %s: %d intentos, %d correos sin cuenta%s',
                $ip['ip'],
                $ip['attempts'],
                $ip['unknown'],
                $ip['sign_ins'] ? ", {$ip['sign_ins']} entradas" : '',
            ));
        }

        return $mail->line('Muchos "correo sin cuenta" desde pocas IPs suelen ser alguien probando listas de contraseñas filtradas. Detalle: php artisan security:report');
    }
}
