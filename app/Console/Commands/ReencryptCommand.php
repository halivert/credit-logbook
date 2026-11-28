<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

/**
 * Second half of an APP_KEY rotation: after moving the old key to
 * APP_PREVIOUS_KEYS, values stored in the database are still encrypted with
 * it. This re-encrypts them with the current key so the old one can be
 * dropped. See docs/key-rotation.md.
 */
class ReencryptCommand extends Command
{
    protected $signature = 'app:reencrypt {--dry-run : Only report what would change}';

    protected $description = 'Re-encrypt stored values with the current APP_KEY';

    /**
     * Columns encrypted with the application key.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'credit_cards' => ['limit'],
        'users' => ['two_factor_secret', 'two_factor_recovery_codes'],
    ];

    public function handle(EncrypterContract $encrypter): int
    {
        if (! $encrypter instanceof Encrypter || $encrypter->getPreviousKeys() === []) {
            $this->components->error('APP_PREVIOUS_KEYS is empty: move the old key there before re-encrypting.');

            return self::FAILURE;
        }

        // Tells apart values that are already using the current key
        $current = new Encrypter($encrypter->getKey(), config('app.cipher'));

        $failed = 0;

        foreach (self::COLUMNS as $table => $columns) {
            $reencrypted = 0;

            DB::table($table)
                ->select(['id', ...$columns])
                ->chunkById(100, function ($rows) use (
                    $table,
                    $columns,
                    $encrypter,
                    $current,
                    &$reencrypted,
                    &$failed,
                ) {
                    foreach ($rows as $row) {
                        $changes = [];

                        foreach ($columns as $column) {
                            $value = $row->{$column};

                            if ($value === null || $this->decrypts($current, $value)) {
                                continue;
                            }

                            try {
                                // Raw payloads, so serialized values stay serialized
                                $plain = $encrypter->decrypt($value, false);
                            } catch (DecryptException) {
                                $this->components->error("{$table}.{$column} of {$row->id} can't be decrypted with any key");
                                $failed++;

                                continue;
                            }

                            $changes[$column] = $encrypter->encrypt($plain, false);
                        }

                        if ($changes === []) {
                            continue;
                        }

                        $reencrypted++;

                        if (! $this->option('dry-run')) {
                            DB::table($table)->where('id', $row->id)->update($changes);
                        }
                    }
                });

            $this->components->twoColumnDetail(
                $table,
                $this->option('dry-run') ? "{$reencrypted} to re-encrypt" : "{$reencrypted} re-encrypted",
            );
        }

        if ($failed > 0) {
            $this->components->error("{$failed} values couldn't be decrypted, keep the previous keys until they're fixed.");

            return self::FAILURE;
        }

        if (! $this->option('dry-run')) {
            $this->components->info('Done. APP_PREVIOUS_KEYS can be removed now.');
        }

        return self::SUCCESS;
    }

    private function decrypts(Encrypter $encrypter, string $value): bool
    {
        try {
            $encrypter->decrypt($value, false);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
