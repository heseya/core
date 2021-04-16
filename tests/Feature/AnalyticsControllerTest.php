<?php

namespace Tests\Feature;

use App\Services\Contracts\AnalyticsServiceContract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testPaymentsTotal(): void
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays(30);

        $this->mock(AnalyticsServiceContract::class, function($mock) {
            $mock->shouldReceive('getPaymentsOverPeriodTotal')
                ->andReturn([
                    'amount' => 1000.0,
                    'count' => 7,
                ]);
        });

        $response = $this->actingAs($this->user)->getJson('/analytics/payments/total', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        $response->assertOk()
            ->assertJson([
                "data" => [
                    'amount' => 1000.0,
                    'count' => 7,
                ],
            ]);
    }

    public function testPaymentsTotalDefault(): void
    {
        $this->mock(AnalyticsServiceContract::class, function($mock) {
            $mock->shouldReceive('getPaymentsOverPeriodTotal')
                ->andReturn([
                    'amount' => 1000.0,
                    'count' => 7,
                ]);
        });

        $response = $this->actingAs($this->user)->getJson('/analytics/payments/total');

        $response->assertOk()
            ->assertJson([
                "data" => [
                    'amount' => 1000.0,
                    'count' => 7,
                ],
            ]);
    }
}
