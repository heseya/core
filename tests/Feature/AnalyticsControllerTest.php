<?php

namespace Tests\Feature;

use App\Services\Contracts\AnalyticsServiceContract;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Traits\RefreshDatabase;

class AnalyticsControllerTest extends TestCase
{


    public function testPaymentsUnauthorized(): void
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays(30);

        $response = $this->getJson('/analytics/payments', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group' => 'total',
        ]);

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testPayments($auth): void
    {
        $this->$auth->givePermissionTo('analytics.payments');

        $to = Carbon::today();
        $from = $to->copy()->subDays(30);

        $this->mock(AnalyticsServiceContract::class, function($mock) {
            $mock->shouldReceive('getPaymentsOverPeriod')
                ->andReturn([
                    'total' => [
                        'amount' => 1000.0,
                        'count' => 7,
                    ],
                ]);
        });

        $response = $this->actingAs($this->$auth)->getJson('/analytics/payments', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group' => 'total',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'total' => [
                        'amount' => 1000.0,
                        'count' => 7,
                    ],
                ],
            ]);
    }
}
