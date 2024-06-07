<?php

declare(strict_types=1);

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
        DB::flushQueryLog();
    }

    public static function findN1(): array
    {
        $queries = [];

        foreach (self::getQueriesExecuted() as $query) {
            if (!array_key_exists($query['query'], $queries)) {
                $queries[$query['query']] = 1;
            } else {
                ++$queries[$query['query']];
            }
        }

        foreach ($queries as $key => $query) {
            if ($query <= 1) {
                unset($queries[$key]);
            }
        }

        asort($queries, SORT_NUMERIC);

        return $queries;
    }

    public function json($method, $uri, array $data = [], array $headers = [], $options = 0)
    {
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
