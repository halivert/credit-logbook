<?php

use App\Providers\RouteServiceProvider;
use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Fortify will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
     */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    |
    | Here you may specify which password broker Fortify can use when a user
    | is resetting their password. This configured value should match one
    | of your password brokers setup in your "auth" configuration file.
    |
     */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | This value defines which model attribute should be considered as your
    | application's "username" field. Typically, this might be the email
    | address of the users but you are free to change this value here.
    |
    | Out of the box, Fortify expects forgot password and reset password
    | requests to have a field named 'email'. If the application uses
    | another name for the field you may define it below as needed.
    |
     */

    // People sign in with their email
    'username' => 'email',

    'email' => 'email',

    // "Hali@Example.com" signs in as "hali@example.com"
    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    |
    | Here you may configure the path where users will get redirected during
    | authentication or password reset when the operations are successful
    | and the user is authenticated. You are free to change this value.
    |
     */

    'home' => RouteServiceProvider::HOME,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    |
    | Here you may specify which prefix Fortify will assign to all the routes
    | that it registers with the application. If necessary, you may change
    | subdomain under which all of the Fortify routes will be available.
    |
     */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify which middleware Fortify will assign to the routes
    | that it registers with the application. If necessary, you may change
    | these middleware but typically this provided default is preferred.
    |
     */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | By default, Fortify will throttle logins to five requests per minute for
    | every email and IP address combination. However, if you would like to
    | specify a custom rate limiter to call then you may specify it here.
    |
     */

    'limiters' => [
        // Null: Fortify throttles the web login itself (5 failed attempts per
        // email and IP), answering on the form and firing Lockout so it shows
        // in the account's activity. The API uses the "login" limiter.
        'login' => null,
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    |
    | Here you may specify if the routes returning views should be disabled as
    | you may not need them when building your own application. This may be
    | especially true if you're writing a custom single-page application.
    |
     */

    // The views are Inertia pages, set in FortifyServiceProvider
    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Some of the Fortify features are optional. You may disable the features
    | by removing them from this array. You're free to only remove some of
    | these features or you can even remove all of these if you need to.
    |
     */

    'features' => [
        ...(env('REGISTRATION', false) ? [Features::registration()] : []),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updatePasswords(),
        // Management routes also ask for the password, already confirmed by
        // the security page
        Features::passkeys(['confirmPassword' => true]),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],


    /*
    |--------------------------------------------------------------------------
    | Passkeys
    |--------------------------------------------------------------------------
    |
    | The user handle shown to authenticators is derived from this secret. A
    | dedicated one keeps it stable when APP_KEY is rotated.
    |
    */

    'passkeys' => [
        // An empty value (as copied from .env.example) falls back to APP_KEY
        'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET') ?: env('APP_KEY'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | The pages people see, in Spanish. The JSON endpoints (/user/..., and
    | /passkeys/... which the passkeys client calls) keep Fortify's paths.
    |
    */

    // Fortify reads them as fortify.paths.<route name>, so dotted names nest
    'paths' => [
        'login' => '/entrar',
        'logout' => '/salir',
        'register' => '/registro',
        'password' => [
            'request' => '/recuperar-password',
            'email' => '/recuperar-password',
            'reset' => '/nuevo-password/{token}',
            'update' => '/nuevo-password',
            'confirm' => '/confirmar-password',
        ],
        'two-factor' => [
            'login' => '/verificacion',
        ],
        'verification' => [
            'notice' => '/verificar-correo',
            'verify' => '/verificar-correo/{id}/{hash}',
            'send' => '/verificar-correo/reenviar',
        ],
    ],

];
