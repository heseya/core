<?php

namespace Tests\Traits;

use Closure;
use Illuminate\Support\Facades\DB;

trait JsonQueryCounter
{
    public int $maxQueryCount = 15;

    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $startQuery = (static::getQueryCount());

        static::trackQueries();
        $json = parent::json($method, $uri, $data, $headers);

//        if ($this->maxQueryCount <= static::getQueryCount() - $startQuery) {
//            Storage::put('json/' . $method . str_replace('/', '.', $uri) . '.json', json_encode(DB::getQueryLog()));
//        }

         dd(DB::getQueryLog());

        $this->assertLessThanOrEqual($this->maxQueryCount, static::getQueryCount() - $startQuery);

        return $json;
    }

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

    public function assertNoQueriesExecuted(Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertQueryCountMatches(0);

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    public function assertQueryCountMatches(int $count, Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertEquals($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    public function assertQueryCountLessThan(int $count, Closure $closure = null): void
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

    public function assertQueryCountGreaterThan(int $count, Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertGreaterThan($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }
}
