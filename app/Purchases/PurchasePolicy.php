<?php

namespace App\Purchases;

use App\CreditCards\CreditCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PurchasePolicy
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
    public function view(User $user, Purchase $purchase): Response
    {
        return $purchase->user->is($user)
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
    public function update(User $user, Purchase $purchase): Response
    {
        if ($purchase->paid_at) return $this->denyAsNotFound();

        return $purchase->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Purchase $purchase): Response
    {
        return $purchase->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }
}
