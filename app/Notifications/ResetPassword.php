<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Laravel's reset password email, sent from the queue: the page answers
 * right away instead of waiting for the mail server, and a failed send is
 * retried by the worker.
 */
class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;
}
