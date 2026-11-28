<?php

namespace Tests\Feature\CreditCards;

use App\CreditCards\CreditCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A card through a year, the way a person uses it: purchases, a purchase in
 * installments, a return and a payment, checking what the card page shows.
 */
class CardLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CreditCard $creditCard;

    private function on(string $day): void
    {
        $this->travelTo(Carbon::parse("{$day} 12:00", 'America/Mexico_City'));
    }

    private function submit(string $route, array $data): void
    {
        $this->actingAs($this->user)
            ->post(route($route, $this->creditCard ?? null), $data)
            ->assertSessionHasNoErrors();
    }

    private function cardPage(): Assert
    {
        $assert = null;

        $this->actingAs($this->user)
            ->get(route('credit-cards.show', $this->creditCard))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$assert) {
                $assert = $page;
            });

        return $assert;
    }

    #[Test]
    public function a_year_with_a_card(): void
    {
        $this->user = User::factory()->create();

        $this->on('2026-10-01');
        $this->actingAs($this->user)->post(route('credit-cards.store'), [
            'name' => 'Oro',
            'closing_date' => 20,
            'due_date' => 9,
            'limit' => '20000',
            'timezone' => 'America/Mexico_City',
        ])->assertSessionHasNoErrors();
        $this->creditCard = $this->user->creditCards()->sole();

        $this->on('2026-10-05');
        $this->submit('credit-cards.purchases.store', [
            'concept' => 'Súper',
            'date' => '2026-10-05',
            'amount' => '500',
        ]);

        $this->on('2026-10-15');
        $this->submit('credit-cards.purchases.store', [
            'is_installment_purchase' => true,
            'concept' => 'Laptop',
            'date' => '2026-10-15',
            'amount' => '12000',
            'installment_count' => 12,
            // Left empty: the even split
            'installment_amount' => '',
        ]);

        $this->on('2026-10-18');
        $this->submit('credit-cards.purchases.store', [
            'is_refund' => true,
            'concept' => 'Devolución',
            'date' => '2026-10-18',
            'amount' => '200',
        ]);

        // Before the first closing: the laptop blocks its whole amount
        $this->cardPage()
            ->where('creditCard.balance_used', 12300)
            ->where('creditCard.available_credit', 7700)
            ->where('creditCard.next_closing_date', '2026-10-20')
            ->where('creditCard.next_due_date', '2026-11-09')
            ->where('installmentPurchases.0.billed_installments', 0)
            ->where('installmentPurchases.0.installment_amount', 1000);

        // The closing bills the first installment, the balance doesn't move
        $this->on('2026-10-21');
        $this->cardPage()
            ->where('creditCard.balance_used', 12300)
            ->where('creditCard.next_closing_date', '2026-11-20')
            ->where('installmentPurchases.0.billed_installments', 1)
            ->where('installmentPurchases.0.remaining_amount', 11000);

        // Paying what was billed: 500 - 200 + 1000
        $this->on('2026-11-05');
        $this->submit('credit-cards.payments.store', ['date' => '2026-11-05', 'amount' => '1300']);
        $this->cardPage()
            ->where('creditCard.balance_used', 11000)
            ->where('creditCard.available_credit', 9000);

        $this->on('2026-11-21');
        $this->cardPage()->where('installmentPurchases.0.billed_installments', 2);

        // After its twelfth closing the laptop is fully billed and leaves the
        // list, but it's still owed until it's paid
        $this->on('2027-09-21');
        $this->cardPage()
            ->has('installmentPurchases', 0)
            ->where('creditCard.balance_used', 11000);
    }
}
