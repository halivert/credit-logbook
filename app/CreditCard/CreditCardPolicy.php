<?php

namespace App\CreditCard;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CreditCardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->exists ? $this->allow() : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CreditCard $creditCard): Response
    {
        return $creditCard->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->exists ? $this->allow() : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CreditCard $creditCard): Response
    {
        return $creditCard->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CreditCard $creditCard): Response
    {
        return $creditCard->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CreditCard $creditCard): Response
    {
        return $creditCard->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CreditCard $creditCard): Response
    {
        return $creditCard->user->is($user)
            ? $this->allow()
            : $this->denyAsNotFound();
    }
}
