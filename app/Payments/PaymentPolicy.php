<?php

namespace App\Payments;

use App\CreditCards\CreditCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, CreditCard $creditCard): Response
    {
        return Gate::inspect('view', $creditCard);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): Response
    {
        return $user->is($payment->user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CreditCard $creditCard): Response
    {
        return Gate::inspect('update', $creditCard);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): Response
    {
        return $user->is($payment->user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): Response
    {
        return $user->is($payment->user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }
}
