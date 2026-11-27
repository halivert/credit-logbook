<?php

namespace Tests\Feature\CreditCards;

use App\CreditCards\CreditCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreditCardTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Oro',
            'due_date' => 5,
            'closing_date' => 20,
            'interest_rate' => '45.50',
            'limit' => '25000.00',
            'timezone' => 'America/Mexico_City',
            ...$overrides,
        ];
    }

    #[Test]
    public function guests_cannot_see_that_credit_card_routes_exist(): void
    {
        $creditCard = CreditCard::factory()->create();

        $this->get(route('credit-cards.index'))->assertNotFound();
        $this->get(route('credit-cards.show', $creditCard))->assertNotFound();
        $this->post(route('credit-cards.store'), $this->validPayload())->assertNotFound();

        $this->assertDatabaseCount(CreditCard::class, 1);
    }

    #[Test]
    public function index_lists_only_the_users_own_cards(): void
    {
        $user = User::factory()->create();
        $own = CreditCard::factory()->count(2)->for($user)->create();
        CreditCard::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('credit-cards.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CreditCards/Index')
                ->has('creditCards.data', 2)
                ->where('creditCards.data.0.id', fn ($id) => $own->pluck('id')->contains($id))
                ->where('creditCards.data.1.id', fn ($id) => $own->pluck('id')->contains($id))
            );
    }

    #[Test]
    public function a_user_can_register_a_credit_card(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('credit-cards.store'), $this->validPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.index'));

        $creditCard = $user->creditCards()->sole();

        $this->assertSame('Oro', $creditCard->name);
        $this->assertSame(5, $creditCard->due_date);
        $this->assertSame(20, $creditCard->closing_date);
        $this->assertSame('45.50', $creditCard->interest_rate);
        $this->assertSame('25000.00', $creditCard->limit);
    }

    #[Test]
    public function the_limit_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('credit-cards.store'), $this->validPayload());

        $raw = $user->creditCards()->toBase()->value('limit');

        $this->assertNotSame('25000.00', $raw);
        $this->assertSame('25000.00', decrypt($raw, false));
    }

    #[Test]
    public function registering_a_card_validates_the_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('credit-cards.store'), $this->validPayload([
                'name' => '',
                'due_date' => 31,
                'closing_date' => 0,
                'interest_rate' => '201',
                'limit' => '-1',
                'timezone' => 'Mars/Olympus_Mons',
            ]))
            ->assertSessionHasErrors([
                'name',
                'due_date',
                'closing_date',
                'interest_rate',
                'limit',
                'timezone',
            ]);

        $this->assertDatabaseCount(CreditCard::class, 0);
    }

    #[Test]
    public function a_user_can_see_their_own_card(): void
    {
        $creditCard = CreditCard::factory()->create();

        $this->actingAs($creditCard->user)
            ->get(route('credit-cards.show', $creditCard))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CreditCards/Show')
                ->where('creditCard.id', $creditCard->id)
            );
    }

    #[Test]
    public function a_user_cannot_see_or_edit_someone_elses_card(): void
    {
        $user = User::factory()->create();
        $creditCard = CreditCard::factory()->create(['name' => 'Ajena']);

        $this->actingAs($user)->get(route('credit-cards.show', $creditCard))->assertNotFound();
        $this->actingAs($user)->get(route('credit-cards.edit', $creditCard))->assertNotFound();
        $this->actingAs($user)
            ->put(route('credit-cards.update', $creditCard), ['name' => 'Mía'])
            ->assertNotFound();

        $this->assertSame('Ajena', $creditCard->fresh()->name);
    }

    #[Test]
    public function a_user_can_update_their_card(): void
    {
        $creditCard = CreditCard::factory()->create(['name' => 'Vieja']);

        $this->actingAs($creditCard->user)
            ->put(route('credit-cards.update', $creditCard), [
                'name' => 'Nueva',
                'closing_date' => 15,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.show', $creditCard));

        $creditCard->refresh();

        $this->assertSame('Nueva', $creditCard->name);
        $this->assertSame(15, $creditCard->closing_date);
    }
}
