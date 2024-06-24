<?php

namespace App\Transaction;

use App\CreditCard\CreditCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, CreditCard $creditCard): Response
    {
        return $creditCard->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): Response
    {
        return $transaction->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CreditCard $creditCard): Response
    {
        return $creditCard->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): Response
    {
        return $transaction->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): Response
    {
        return $transaction->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }
}
