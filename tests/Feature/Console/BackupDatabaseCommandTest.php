<?php

namespace Tests\Feature\Console;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/backups-'.uniqid();

        config([
            'backup.path' => $this->directory,
            'backup.keep_days' => 30,
            'database.default' => 'mariadb',
            'database.connections.mariadb' => [
                'driver' => 'mariadb',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'tarjetero',
                'username' => 'tarjetero',
                'password' => 'se"creta',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    /** Pretends to be mariadb-dump: writes the file it was asked for */
    private function fakeDump(int $exitCode = 0): void
    {
        Process::fake(function (PendingProcess $process) use ($exitCode) {
            if ($exitCode === 0) {
                file_put_contents($process->command[6], gzencode('-- dump'));
            }

            return Process::result(exitCode: $exitCode, errorOutput: $exitCode ? 'Access denied' : '');
        });
    }

    #[Test]
    public function it_dumps_the_database_and_deletes_old_backups(): void
    {
        File::ensureDirectoryExists($this->directory);
        touch($old = $this->directory.'/tarjetero-old.sql.gz', now()->subDays(31)->getTimestamp());
        touch($recent = $this->directory.'/tarjetero-recent.sql.gz', now()->subDays(29)->getTimestamp());
        touch($other = $this->directory.'/notes.txt', now()->subDays(100)->getTimestamp());

        $this->fakeDump();

        $this->artisan('db:backup')->assertSuccessful();

        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($recent);
        $this->assertFileExists($other);
        $this->assertCount(2, File::glob($this->directory.'/tarjetero-*.sql.gz'));

        // The password never goes in the command line
        Process::assertRan(fn (PendingProcess $process) => ! str_contains(implode(' ', $process->command), 'se"creta'));
    }

    #[Test]
    public function a_failed_dump_leaves_no_file_and_fails(): void
    {
        $this->fakeDump(exitCode: 2);

        $this->artisan('db:backup')->assertFailed();

        $this->assertSame([], File::glob($this->directory.'/*.sql.gz'));
    }

    #[Test]
    public function only_mariadb_or_mysql_can_be_backed_up(): void
    {
        config(['database.default' => 'sqlite']);
        Process::fake();

        $this->artisan('db:backup')->assertFailed();

        Process::assertNothingRan();
    }

    #[Test]
    public function it_runs_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('db:backup');
    }
}
