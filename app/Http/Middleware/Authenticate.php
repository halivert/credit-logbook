<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): void
    {
        abort(Response::HTTP_NOT_FOUND);
    }

    protected function unauthenticated($request, array $guards): void
    {
        abort(Response::HTTP_NOT_FOUND);
    }
}
