<?php

namespace App\Providers;

use App\API\CreditCard\CreditCard;
use App\API\CreditCard\CreditCardPolicy;
use App\API\Transaction\Transaction;
use App\API\Transaction\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        CreditCard::class => CreditCardPolicy::class,
        Transaction::class => TransactionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
