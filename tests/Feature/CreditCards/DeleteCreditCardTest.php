<?php

namespace Tests\Feature\CreditCards;

use App\CreditCards\CreditCard;
use App\Models\User;
use App\Payments\Payment;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteCreditCardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function deleting_a_card_removes_it_and_its_data_for_good(): void
    {
        $creditCard = CreditCard::factory()->create();
        Purchase::factory()->create(['credit_card_id' => $creditCard->id]);
        Payment::factory()->create(['credit_card_id' => $creditCard->id]);

        $this->actingAs($creditCard->user)
            ->delete(route('credit-cards.destroy', $creditCard))
            ->assertRedirect(route('credit-cards.index'));

        $this->assertNull(CreditCard::withTrashed()->find($creditCard->id));
        $this->assertDatabaseMissing('purchases', ['credit_card_id' => $creditCard->id]);
        $this->assertDatabaseMissing('payments', ['credit_card_id' => $creditCard->id]);
    }

    #[Test]
    public function nobody_else_can_delete_it(): void
    {
        $creditCard = CreditCard::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('credit-cards.destroy', $creditCard))
            ->assertNotFound();

        $this->assertNotNull($creditCard->fresh());
    }
}
