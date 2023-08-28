<?php

namespace Tests\Feature\Models;

use App\API\CreditCard\CreditCard;
use App\API\Transaction\Transaction;
use App\API\Transaction\v1\TransactionResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    private User $user;

    private CreditCard $creditCard;

    /**
     * @before
     */
    public function set_up(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->creditCard = $this->user->creditCards()->create(
            CreditCard::factory()->make()->toArray()
        );

        $this->assertModelExists($this->creditCard);
    }

    /**
     * @return array<string,array<int,string>>
     */
    public function routes_data_provider(): array
    {
        return [
            'index' => [
                'credit-cards.transactions.index',
                'api/v1/credit-cards/{creditCard}/transactions',
                'GET'
            ],
            'store' => [
                'credit-cards.transactions.store',
                'api/v1/credit-cards/{creditCard}/transactions',
                'POST'
            ],
            'show' => [
                'transactions.show',
                'api/v1/transactions/{transaction}',
                'GET'
            ],
            'update' => [
                'transactions.update',
                'api/v1/transactions/{transaction}',
                'PUT'
            ],
            'destroy' => [
                'transactions.destroy',
                'api/v1/transactions/{transaction}',
                'DELETE'
            ]
        ];
    }

    #[Test]
    #[DataProvider('routes_data_provider')]
    public function it_should_have_route(
        string $routeName,
        string $uri,
        string $method
    ): void {
        $routes = Route::getRoutes();

        $route = $routes->getByName($routeName);

        $this->assertEquals($uri, $route->uri);

        $this->assertContains($method, $route->methods);

        // Every transaction route is behind auth
        $this->assertContains('auth:sanctum', $route->action['middleware']);
    }

    /**
     * Store transactions
     */

    #[Test]
    public function guest_user_should_not_be_able_to_register_transactions(): void
    {
        $response = $this->postJson(
            route('credit-cards.transactions.store', $this->creditCard),
            Transaction::factory()->make()->toArray()
        );

        $response->assertNotFound();
        $this->assertDatabaseCount(Transaction::class, 0);
    }

    #[Test]
    public function user_should_be_able_to_register_a_transaction(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            route('credit-cards.transactions.store', $this->creditCard),
            Transaction::factory()->make()->toArray()
        );

        $response->assertCreated();
        $this->assertDatabaseCount(Transaction::class, 1);
        $this->assertDatabaseHas(Transaction::class, [
            'id' => $response->json()['id']
        ]);
    }

    #[Test]
    public function user_should_not_be_able_to_register_transaction_in_not_their_own_card(): void
    {
        $creditCard = CreditCard::factory()->create();

        $response = $this->actingAs($this->user)->postJson(
            route('credit-cards.transactions.store', $creditCard),
            Transaction::factory()->make()->toArray()
        );

        $response->assertNotFound();
        $this->assertDatabaseCount(Transaction::class, 0);
    }

    /**
     * View transactions
     */

    #[Test]
    public function user_should_be_able_to_see_own_transactions(): void
    {
        $transactions = Transaction::factory()
            ->count(2)
            ->for($this->creditCard)
            ->create();

        $response = $this->actingAs($this->user)->getJson(
            route('credit-cards.transactions.index', $this->creditCard)
        );

        $response->assertOk();

        $this->assertCount($transactions->count(), $response->json()['data']);

        $this->assertEquals(
            $transactions->pluck('id'),
            collect($response->json()['data'])
                ->map(fn ($item) => $item['id'])
        );
    }

    #[Test]
    public function user_should_not_be_able_to_see_not_own_transactions(): void
    {
        $creditCard = CreditCard::factory()->create();

        Transaction::factory()
            ->count(3)
            ->for($creditCard)
            ->create();

        $response = $this->actingAs($this->user)->getJson(
            route('credit-cards.transactions.index', $creditCard)
        );

        $response->assertNotFound();
    }

    #[Test]
    public function user_should_be_able_to_see_own_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for($this->creditCard)
            ->create();

        $response = $this->actingAs($this->user)->getJson(
            route('transactions.show', $transaction)
        );

        $response->assertOk();

        $response->assertJsonStructure([
            'id',
            'credit_card_id',
            'concept',
            'datetime',
            'amount',
            'created_at',
            'updated_at',
            'links' => [
                'self'
            ]
        ]);

        $response->assertJson($transaction->toArray());
    }

    #[Test]
    public function user_should_not_be_able_to_see_not_own_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for(CreditCard::factory())
            ->create();

        $response = $this->actingAs($this->user)->getJson(
            route('transactions.show', $transaction)
        );

        $response->assertNotFound();
    }

    /**
     * Update transactions
     */

    #[Test]
    public function user_should_be_able_to_update_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for($this->creditCard)
            ->create();

        $today = today();

        $response = $this->actingAs($this->user)->putJson(
            route('transactions.update', $transaction),
            ['datetime' => $today]
        );

        $response->assertOk();
        $this->assertDatabaseHas($transaction, ['datetime' => $today]);
    }

    #[Test]
    public function user_should_not_be_able_to_update_others_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for(CreditCard::factory())
            ->create();

        $response = $this->actingAs($this->user)->putJson(
            route('transactions.update', $transaction),
            ['concept' => 'CONCEPT']
        );

        $response->assertNotFound();
        $this->assertDatabaseMissing($transaction, ['concept' => 'CONCEPT']);
    }

    /**
     * Delete transactions
     */

    #[Test]
    public function user_should_be_able_to_delete_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for($this->creditCard)
            ->create();

        $response = $this->actingAs($this->user)->deleteJson(
            route('transactions.destroy', $transaction)
        );

        $response->assertNoContent();
        $this->assertModelMissing($transaction);
    }

    #[Test]
    public function user_should_not_be_able_to_delete_others_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for(CreditCard::factory())
            ->create();

        $response = $this->actingAs($this->user)->deleteJson(
            route('transactions.destroy', $transaction)
        );

        $response->assertNotFound();
        $this->assertModelExists($transaction);
    }
}
