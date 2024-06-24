<?php

namespace App\Providers;

use App\CreditCard\CreditCard;
use App\CreditCard\CreditCardPolicy;
use App\Transaction\Transaction;
use App\Transaction\TransactionPolicy;
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
