<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * A throwaway key shared by every test in the process, so no key has to
     * be committed to the repository.
     */
    private static ?string $testAppKey = null;

    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = self::$testAppKey
            ??= 'base64:' . base64_encode(random_bytes(32));

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
