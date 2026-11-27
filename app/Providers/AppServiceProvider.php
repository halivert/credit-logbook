<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(!$this->app->isProduction());

        $this->useMillisecondPrecisionDates(DB::connection());
    }

    /**
     * Timestamps are stored with millisecond precision (`timestamp(3)`), so
     * the query grammar must format dates with milliseconds too.
     */
    private function useMillisecondPrecisionDates(Connection $connection): void
    {
        $grammar = match ($connection->getDriverName()) {
            'mariadb' => new class($connection) extends MariaDbGrammar {
                public function getDateFormat()
                {
                    return 'Y-m-d H:i:s.v';
                }
            },
            'mysql' => new class($connection) extends MySqlGrammar {
                public function getDateFormat()
                {
                    return 'Y-m-d H:i:s.v';
                }
            },
            'sqlite' => new class($connection) extends SQLiteGrammar {
                public function getDateFormat()
                {
                    return 'Y-m-d H:i:s.v';
                }
            },
            default => null,
        };

        if ($grammar instanceof Grammar) {
            $connection->setQueryGrammar($grammar);
        }
    }
}
