<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Config;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticClientFactory;
use JeroenG\Explorer\Infrastructure\Elastic\FakeResponse;
use JeroenG\Explorer\Infrastructure\Scout\ElasticEngine;

trait ElasticTest
{
    public function fakeElastic(): void
    {
        $response = fopen(base_path('tests/Support/fake-response.json'), 'rb');

        $this->instance(
            ElasticClientFactory::class,
            ElasticClientFactory::fake(new FakeResponse(200, $response)),
        );
    }

    public function assertElasticQuery(array $query, ?int $limit = null, ?array $sort = null): void
    {
        $sort ??= [];
        $this->assertEquals(
            [
                'query' => $query,
                'from' => 0,
                'size' => $limit ?? Config::get('pagination.per_page'),
                'track_total_hits' => true,
            ] + $sort,
            ElasticEngine::debug()->array(),
            'Failed to assert that elastic query matched the expected',
        );
    }
}
