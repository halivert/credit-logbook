<?php

namespace Tests\Feature\Reconciliations;

use App\CreditCards\CreditCard;
use App\Models\User;
use App\Payments\Payment;
use App\Purchases\Purchase;
use App\Reconciliations\Adjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Closing on the 20th, due on the 9th, today is November 27th: the closing
 * being reconciled is November 20th, its period October 21st to 20th.
 */
class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-27 12:00', 'America/Mexico_City'));

        $this->creditCard = CreditCard::factory()->create([
            'closing_date' => 20,
            'due_date' => 9,
            'limit' => '30000',
            'timezone' => 'America/Mexico_City',
        ]);

        Purchase::factory()->for($this->creditCard)->create(['date' => '2026-11-10', 'amount' => '850.50']);
    }

    private function page(): Assert
    {
        $assert = null;

        $this->actingAs($this->creditCard->user)
            ->get(route('credit-cards.reconciliation.show', $this->creditCard))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$assert) {
                $assert = $page->component('Reconciliations/Show');
            });

        return $assert;
    }

    private function cardDifference(?int $difference): void
    {
        $this->actingAs($this->creditCard->user)
            ->get(route('credit-cards.show', $this->creditCard))
            ->assertInertia(fn (Assert $page) => $page
                ->where('creditCard.current_statement.bank_difference', $difference)
            );
    }

    private function bankSays(string $amount): void
    {
        $this->actingAs($this->creditCard->user)
            ->put(route('credit-cards.reconciliation.update', $this->creditCard), [
                'to_avoid_interest' => $amount,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.reconciliation.show', $this->creditCard));
    }

    #[Test]
    public function it_compares_only_once_the_bank_amount_is_known(): void
    {
        $this->page()
            ->where('statement.closing_date', '2026-11-20')
            ->where('statement.to_avoid_interest', 850.5)
            ->where('reconciliation', null);

        $this->bankSays('850.50');

        $this->page()
            ->where('bankAmount', '850.50')
            ->where('reconciliation.difference', 0)
            ->where('reconciliation.matched', true);
    }

    #[Test]
    public function a_missing_purchase_shows_up_and_adding_it_matches(): void
    {
        $this->cardDifference(null);

        $this->bankSays('1200.50');

        $this->cardDifference(350);

        $this->page()
            ->where('reconciliation.difference', 350)
            ->where('reconciliation.matched', false);

        // Adding it from the reconciliation starts on the closing and goes back
        $this->actingAs($this->creditCard->user)
            ->get(route('credit-cards.purchases.create', [$this->creditCard, 'from' => 'reconciliation']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('returnTo', 'reconciliation')
                ->where('defaultDate', '2026-11-20')
            );

        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'concept' => 'Farmacia',
                'date' => '2026-11-18',
                'amount' => '350',
                'return_to' => 'reconciliation',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.reconciliation.show', $this->creditCard));

        $this->page()->where('reconciliation.matched', true);
        $this->cardDifference(0);
    }

    #[Test]
    public function an_adjustment_settles_a_difference_that_cant_be_found(): void
    {
        // The logbook has 850.50, the bank asks for 600: a payment is missing
        $this->bankSays('600');

        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.adjustments.store', $this->creditCard), [
                // Ignored: the server calculates it
                'amount' => '99999',
            ])
            ->assertRedirect(route('credit-cards.reconciliation.show', $this->creditCard));

        $adjustment = $this->creditCard->adjustments()->sole();

        $this->assertSame('-250.50', $adjustment->amount);
        $this->assertSame('2026-11-20', $adjustment->date->toDateString());
        $this->assertEqualsWithDelta(600, $this->creditCard->fresh()->balance_used, 0.001);

        $this->page()
            ->where('reconciliation.matched', true)
            ->has('adjustments', 1);

        // Undoing it brings the difference back
        $this->actingAs($this->creditCard->user)
            ->delete(route('adjustments.destroy', $adjustment))
            ->assertRedirect(route('credit-cards.reconciliation.show', $this->creditCard));

        $this->page()->where('reconciliation.difference', -250.5);
    }

    #[Test]
    public function nothing_is_adjusted_when_it_already_matches_or_the_bank_amount_is_unknown(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.adjustments.store', $this->creditCard))
            ->assertStatus(422);

        $this->bankSays('850.50');

        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.adjustments.store', $this->creditCard));

        $this->assertDatabaseCount(Adjustment::class, 0);
    }

    #[Test]
    public function it_lists_the_closed_period_like_the_statement(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['date' => '2026-10-20', 'concept' => 'Del periodo anterior']);
        Purchase::factory()->for($this->creditCard)->create(['date' => '2026-11-21', 'concept' => 'Del siguiente periodo']);
        Purchase::factory()->for($this->creditCard)->create(['date' => '2026-10-25', 'amount' => '-100', 'concept' => 'Devolución']);
        Purchase::factory()->for($this->creditCard)->create([
            'date' => '2026-08-27',
            'concept' => 'Laptop',
            'amount' => '12000',
            'installment_count' => 12,
            'installment_amount' => '1000',
        ]);
        Payment::factory()->for($this->creditCard)->create(['date' => '2026-11-05', 'amount' => '500']);

        $this->page()
            ->has('lines', 4)
            ->where('lines.0.concept', 'Devolución')
            ->where('lines.0.kind', 'refund')
            ->where('lines.1.kind', 'payment')
            ->where('lines.1.amount', -500)
            ->where('lines.2.amount', 850.5)
            ->where('lines.3.concept', 'Laptop · mensualidad 3 de 12')
            ->where('lines.3.amount', 1000);
    }

    #[Test]
    public function the_bank_amount_is_validated(): void
    {
        foreach (['-1', '0.001', 'mucho'] as $amount) {
            $this->actingAs($this->creditCard->user)
                ->put(route('credit-cards.reconciliation.update', $this->creditCard), [
                    'to_avoid_interest' => $amount,
                ])
                ->assertSessionHasErrors('to_avoid_interest');
        }

        $this->assertSame(0, $this->creditCard->bankStatements()->count());
    }

    #[Test]
    public function a_user_cannot_reconcile_someone_elses_card(): void
    {
        $adjustment = Adjustment::factory()->for($this->creditCard)->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('credit-cards.reconciliation.show', $this->creditCard))->assertNotFound();
        $this->actingAs($intruder)
            ->put(route('credit-cards.reconciliation.update', $this->creditCard), ['to_avoid_interest' => '1'])
            ->assertNotFound();
        $this->actingAs($intruder)->post(route('credit-cards.adjustments.store', $this->creditCard))->assertNotFound();
        $this->actingAs($intruder)->delete(route('adjustments.destroy', $adjustment))->assertNotFound();

        $this->assertModelExists($adjustment);
        $this->assertSame(0, $this->creditCard->bankStatements()->count());
    }
}
