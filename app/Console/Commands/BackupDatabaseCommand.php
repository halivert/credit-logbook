<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Dumps the MariaDB database to a gzipped file and deletes the ones older
 * than config('backup.keep_days').
 */
class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Back up the database and delete old backups';

    public function handle(): int
    {
        $connection = config('database.connections.'.config('database.default'));

        if (! in_array($connection['driver'], ['mariadb', 'mysql'])) {
            $this->components->error("Solo se respalda MariaDB o MySQL, no {$connection['driver']}.");

            return self::FAILURE;
        }

        $directory = config('backup.path');
        File::ensureDirectoryExists($directory, 0700);

        $file = $directory.'/'.$connection['database'].'-'.now()->format('Y-m-d-His').'.sql.gz';

        if (! $this->dump($connection, $file)) {
            return self::FAILURE;
        }

        chmod($file, 0600);
        $this->components->info('Respaldo: '.$file.' ('.number_format(filesize($file) / 1024).' KB)');

        $deleted = $this->prune($directory, $connection['database']);
        $this->components->info("Respaldos de más de ".config('backup.keep_days')." días borrados: {$deleted}");

        return self::SUCCESS;
    }

    private function dump(array $connection, string $file): bool
    {
        // Credentials go in a file only this user can read, not in the
        // command line (anyone on the server can see it with ps)
        $credentials = tempnam(sys_get_temp_dir(), 'backup');
        chmod($credentials, 0600);
        file_put_contents($credentials, implode("\n", [
            '[client]',
            'user="'.addcslashes($connection['username'], '"\\').'"',
            'password="'.addcslashes((string) $connection['password'], '"\\').'"',
            'host="'.$connection['host'].'"',
            'port='.$connection['port'],
            '',
        ]));

        try {
            $result = Process::timeout(600)->run([
                'bash', '-c',
                // MariaDB servers have mariadb-dump; MySQL clients mysqldump
                'set -o pipefail; dump=$(command -v mariadb-dump || command -v mysqldump) || exit 127;'
                .' "$dump" --defaults-extra-file="$1" --single-transaction --quick "$2" | gzip > "$3"',
                'backup', $credentials, $connection['database'], $file,
            ]);
        } finally {
            unlink($credentials);
        }

        if ($result->failed()) {
            File::delete($file);
            $this->components->error('No se pudo respaldar: '.trim($result->errorOutput() ?: 'código '.$result->exitCode()));

            return false;
        }

        return true;
    }

    private function prune(string $directory, string $database): int
    {
        $limit = now()->subDays(config('backup.keep_days'))->getTimestamp();

        $old = collect(File::glob($directory.'/'.$database.'-*.sql.gz'))
            ->filter(fn (string $path) => File::lastModified($path) < $limit);

        File::delete($old->all());

        return $old->count();
    }
}
