<?php

namespace Tests\Feature\Console;

use App\CreditCards\CreditCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReencryptCommandTest extends TestCase
{
    use RefreshDatabase;

    private function newKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /**
     * Same as editing APP_KEY / APP_PREVIOUS_KEYS and reloading the config.
     */
    private function useKeys(string $key, array $previous = []): void
    {
        config(['app.key' => $key, 'app.previous_keys' => $previous]);

        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');
    }

    #[Test]
    public function rotating_the_key_keeps_encrypted_data_readable(): void
    {
        $oldKey = config('app.key');

        $creditCard = CreditCard::factory()->create(['limit' => '25000.00']);
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => encrypt('SECRET'),
            'two_factor_recovery_codes' => encrypt(json_encode(['a', 'b'])),
        ])->save();

        $this->useKeys($this->newKey(), [$oldKey]);

        $this->artisan('app:reencrypt')
            ->expectsOutputToContain('1 re-encrypted')
            ->assertSuccessful();

        // Without the old key everything is still readable
        $this->useKeys(config('app.key'));

        $this->assertSame('25000.00', $creditCard->fresh()->limit);
        $this->assertSame('SECRET', decrypt($user->fresh()->two_factor_secret));
        $this->assertSame(['a', 'b'], json_decode(decrypt($user->fresh()->two_factor_recovery_codes)));
    }

    #[Test]
    public function running_it_again_changes_nothing(): void
    {
        $oldKey = config('app.key');
        CreditCard::factory()->create();

        $this->useKeys($this->newKey(), [$oldKey]);
        $this->artisan('app:reencrypt')->assertSuccessful();

        $raw = DB::table('credit_cards')->value('limit');

        $this->artisan('app:reencrypt')
            ->expectsOutputToContain('0 re-encrypted')
            ->assertSuccessful();

        $this->assertSame($raw, DB::table('credit_cards')->value('limit'));
    }

    #[Test]
    public function a_dry_run_does_not_write(): void
    {
        $oldKey = config('app.key');
        CreditCard::factory()->create();
        $raw = DB::table('credit_cards')->value('limit');

        $this->useKeys($this->newKey(), [$oldKey]);

        $this->artisan('app:reencrypt --dry-run')
            ->expectsOutputToContain('1 to re-encrypt')
            ->assertSuccessful();

        $this->assertSame($raw, DB::table('credit_cards')->value('limit'));
    }

    #[Test]
    public function soft_deleted_cards_are_re_encrypted_too(): void
    {
        $oldKey = config('app.key');
        $creditCard = CreditCard::factory()->create(['limit' => '100.00']);
        $creditCard->delete();

        $this->useKeys($this->newKey(), [$oldKey]);
        $this->artisan('app:reencrypt')->assertSuccessful();
        $this->useKeys(config('app.key'));

        $this->assertSame('100.00', CreditCard::withTrashed()->find($creditCard->id)->limit);
    }

    #[Test]
    public function it_refuses_to_run_without_previous_keys(): void
    {
        $this->artisan('app:reencrypt')
            ->expectsOutputToContain('APP_PREVIOUS_KEYS is empty')
            ->assertFailed();
    }

    #[Test]
    public function values_no_key_can_decrypt_are_reported_and_left_alone(): void
    {
        $creditCard = CreditCard::factory()->create();
        $lostKeyValue = DB::table('credit_cards')->value('limit');

        // Neither the new key nor the previous one is the original
        $this->useKeys($this->newKey(), [$this->newKey()]);

        $this->artisan('app:reencrypt')
            ->expectsOutputToContain("can't be decrypted")
            ->assertFailed();

        $this->assertSame($lostKeyValue, DB::table('credit_cards')->where('id', $creditCard->id)->value('limit'));
    }
}
