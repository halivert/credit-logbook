<?php

namespace Tests\Feature\Purchases;

use App\CreditCards\CreditCard;
use App\Models\User;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditCard = CreditCard::factory()->create();
    }

    #[Test]
    public function a_user_can_register_a_single_payment_purchase(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'is_installment_purchase' => false,
                'is_refund' => false,
                'concept' => 'Super',
                'datetime' => today()->toDateString(),
                'amount' => '1250.50',
                // Installment fields are ignored for single payment purchases
                'installment_count' => 12,
                'installment_amount' => '100',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.show', $this->creditCard));

        $purchase = $this->creditCard->purchases()->sole();

        $this->assertSame('Super', $purchase->concept);
        $this->assertSame('1250.50', $purchase->amount);
        $this->assertNull($purchase->installment_count);
        $this->assertNull($purchase->getRawOriginal('installment_amount'));
        $this->assertFalse($purchase->is_installment_purchase);
    }

    #[Test]
    public function a_user_can_register_an_installment_purchase(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'is_installment_purchase' => true,
                'concept' => 'Laptop',
                'datetime' => today()->toDateString(),
                'amount' => '12000',
                'installment_count' => 12,
                'installment_amount' => '1000',
                'commission' => '150',
            ])
            ->assertSessionHasNoErrors();

        $purchase = $this->creditCard->purchases()->sole();

        $this->assertTrue($purchase->is_installment_purchase);
        $this->assertSame(12, $purchase->installment_count);
        $this->assertSame('150.00', $purchase->commission);
    }

    #[Test]
    public function purchases_cannot_be_dated_in_the_future_or_exceed_their_amount_per_installment(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'is_installment_purchase' => true,
                'concept' => 'Laptop',
                'datetime' => today()->addDays(2)->toDateString(),
                'amount' => '1000',
                'installment_count' => 3,
                'installment_amount' => '1001',
            ])
            ->assertSessionHasErrors(['datetime', 'installment_amount']);

        $this->assertDatabaseCount(Purchase::class, 0);
    }

    #[Test]
    public function a_user_cannot_register_purchases_on_someone_elses_card(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'is_installment_purchase' => false,
                'is_refund' => false,
                'concept' => 'Super',
                'datetime' => today()->toDateString(),
                'amount' => '100',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount(Purchase::class, 0);
    }

    #[Test]
    public function a_user_can_update_and_delete_their_purchase(): void
    {
        $purchase = Purchase::factory()->for($this->creditCard)->create();

        $this->actingAs($this->creditCard->user)
            ->put(route('purchases.update', $purchase), [
                'concept' => 'Corregido',
                'datetime' => today()->toDateString(),
                'amount' => '99.99',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.show', $this->creditCard));

        $this->assertSame('Corregido', $purchase->fresh()->concept);
        $this->assertSame('99.99', $purchase->fresh()->amount);

        $this->actingAs($this->creditCard->user)
            ->delete(route('purchases.destroy', $purchase))
            ->assertRedirect(route('credit-cards.show', $this->creditCard));

        $this->assertModelMissing($purchase);
    }


    #[Test]
    public function a_user_cannot_touch_someone_elses_purchase(): void
    {
        $purchase = Purchase::factory()->for($this->creditCard)->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('purchases.edit', $purchase))->assertNotFound();
        $this->actingAs($intruder)->delete(route('purchases.destroy', $purchase))->assertNotFound();

        $this->assertModelExists($purchase);
    }

    #[Test]
    public function a_return_is_stored_as_a_credit(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'is_refund' => true,
                // A return can't be in installments
                'is_installment_purchase' => true,
                'installment_count' => 3,
                'concept' => 'Devolución Amazon',
                'datetime' => today()->toDateString(),
                'amount' => '300',
            ])
            ->assertSessionHasNoErrors();

        $refund = $this->creditCard->purchases()->sole();

        $this->assertSame('-300.00', $refund->amount);
        $this->assertTrue($refund->is_refund);
        $this->assertNull($refund->installment_count);

        $this->actingAs($this->creditCard->user)
            ->put(route('purchases.update', $refund), [
                'concept' => 'Devolución Amazon',
                'datetime' => today()->toDateString(),
                'amount' => '350',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('-350.00', $refund->fresh()->amount);
    }

    #[Test]
    public function editing_an_installment_purchase_shows_the_installment_without_commission(): void
    {
        $purchase = Purchase::factory()->for($this->creditCard)->create([
            'amount' => 1200,
            'installment_count' => 12,
            'installment_amount' => 100,
            'commission' => 35,
        ]);

        $this->actingAs($this->creditCard->user)
            ->get(route('purchases.edit', $purchase))
            ->assertInertia(fn (Assert $page) => $page
                ->where('baseInstallmentAmount', '100.00')
            );
    }
}
