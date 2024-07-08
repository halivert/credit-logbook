<?php

namespace App\Providers;

use App\CreditCards\{CreditCard, CreditCardPolicy};
use App\Payments\{Payment, PaymentPolicy};
use App\Purchases\{Purchase, PurchasePolicy};
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
        Purchase::class => PurchasePolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
