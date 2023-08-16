<?php

namespace Tests\Feature\SalesChannels;

use Domain\SalesChannel\Models\SalesChannel;
use Support\Enum\Status;
use Tests\TestCase;

class SalesChannelsCrudTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function textIndex(string $user): void
    {
        $public = SalesChannel::factory()->create(['status' => Status::ACTIVE]);
        SalesChannel::factory()->create(['status' => Status::INACTIVE]);
        SalesChannel::factory()->create(['status' => Status::HIDDEN]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/sales-channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $public->getKey()]);
    }
}
