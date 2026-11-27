<?php

namespace Tests\Feature\Payments;

use App\CreditCards\CreditCard;
use App\Models\User;
use App\Payments\Payment;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditCard = CreditCard::factory()->create();
    }

    private function installmentPurchase(int $count = 3): Purchase
    {
        return Purchase::factory()->for($this->creditCard)->create([
            'amount' => 100 * $count,
            'installment_count' => $count,
            'installment_amount' => 100,
        ]);
    }

    #[Test]
    public function the_payment_form_lists_only_unpaid_purchases(): void
    {
        $unpaid = Purchase::factory()->for($this->creditCard)->create();
        Purchase::factory()->for($this->creditCard)->create(['paid_at' => now()]);
        $installments = $this->installmentPurchase();

        $this->actingAs($this->creditCard->user)
            ->get(route('credit-cards.payments.create', $this->creditCard))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payments/Edit')
                ->has('purchases', 1)
                ->where('purchases.0.id', $unpaid->id)
                ->has('installmentPurchases', 1)
                ->where('installmentPurchases.0.id', $installments->id)
            );
    }

    #[Test]
    public function paying_settles_the_selected_single_payment_purchases(): void
    {
        $selected = Purchase::factory()->for($this->creditCard)->create();
        $notSelected = Purchase::factory()->for($this->creditCard)->create();

        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'datetime' => today()->toDateString(),
                'amount' => '500',
                'purchases' => [$selected->id],
                'installment_purchases' => [],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.show', $this->creditCard));

        $payment = $this->creditCard->payments()->sole();

        $this->assertEquals(500, $payment->amount);
        $this->assertNotNull($selected->fresh()->paid_at);
        $this->assertNull($notSelected->fresh()->paid_at);
        $this->assertTrue($payment->purchases->contains($selected));
    }

    #[Test]
    public function paying_an_installment_records_its_number_without_settling_the_purchase(): void
    {
        $purchase = $this->installmentPurchase(3);

        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'datetime' => today()->toDateString(),
                'amount' => '100',
                'purchases' => [],
                'installment_purchases' => [
                    ['id' => $purchase->id, 'installment_number' => 1],
                ],
            ])
            ->assertSessionHasNoErrors();

        $purchase = $purchase->fresh();

        $this->assertNull($purchase->paid_at);
        $this->assertSame(1, $purchase->paid_months);
        $this->assertSame(1, $purchase->payments->sole()->pivot->installment_number);
    }

    #[Test]
    public function paying_the_last_installment_settles_the_purchase(): void
    {
        $purchase = $this->installmentPurchase(3);

        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'datetime' => today()->toDateString(),
                'amount' => '100',
                'purchases' => [],
                'installment_purchases' => [
                    // Over the count is capped to the last installment
                    ['id' => $purchase->id, 'installment_number' => 7],
                ],
            ])
            ->assertSessionHasNoErrors();

        $purchase = $purchase->fresh();

        $this->assertNotNull($purchase->paid_at);
        $this->assertSame(3, $purchase->payments->sole()->pivot->installment_number);
    }

    #[Test]
    public function a_payment_can_be_registered_without_linking_purchases(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'datetime' => today()->toDateString(),
                'amount' => '300',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.show', $this->creditCard));

        $this->assertCount(0, $this->creditCard->payments()->sole()->purchases);
    }

    #[Test]
    public function payments_are_validated(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'datetime' => today()->addDay()->toDateString(),
                'amount' => '0',
                'purchases' => ['not-a-purchase'],
            ])
            ->assertSessionHasErrors(['datetime', 'amount', 'purchases.0']);

        $this->assertDatabaseCount(Payment::class, 0);
    }

    #[Test]
    public function a_user_cannot_pay_someone_elses_card(): void
    {
        $purchase = Purchase::factory()->for($this->creditCard)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'datetime' => today()->toDateString(),
                'amount' => '100',
                'purchases' => [$purchase->id],
                'installment_purchases' => [],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount(Payment::class, 0);
        $this->assertNull($purchase->fresh()->paid_at);
    }
}
