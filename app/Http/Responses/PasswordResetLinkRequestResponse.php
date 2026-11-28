<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Asking for a password reset with an email that has no account answers
 * exactly like when it has one, so nobody can find out which emails do.
 * Other failures, like asking too often, are still reported.
 */
class PasswordResetLinkRequestResponse extends FailedPasswordResetLinkRequestResponse
{
    public function toResponse($request)
    {
        if ($this->status === Password::INVALID_USER) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))
                ->toResponse($request);
        }

        return parent::toResponse($request);
    }
}
