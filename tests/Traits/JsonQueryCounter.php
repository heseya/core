<?php

namespace Tests\Traits;

use Closure;
use Illuminate\Support\Facades\DB;

trait JsonQueryCounter
{
    public static function getQueryCount(): int
    {
        return count(self::getQueriesExecuted());
    }

    public static function getQueriesExecuted(): array
    {
        return DB::getQueryLog();
    }

    public static function trackQueries(): void
    {
        DB::enableQueryLog();
    }

    public function json($method, $uri, array $data = [], array $headers = [], $options = 0)
    {
        static::getQueryCount();
        static::trackQueries();

        return parent::json($method, $uri, $data, $headers, $options);
    }

    public function assertQueryCountLessThan(int $count, ?Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertLessThan($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }
}
