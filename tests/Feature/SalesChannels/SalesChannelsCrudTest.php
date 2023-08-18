<?php

declare(strict_types=1);

namespace Tests\Feature\SalesChannels;

use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Support\Str;
use Support\Enum\Status;
use Tests\TestCase;

final class SalesChannelsCrudTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        /** @var SalesChannel $channel */
        $channel = SalesChannel::query()->first(); // default channel from migration
        SalesChannel::factory()->create(['status' => Status::INACTIVE]);
        SalesChannel::factory()->create(['status' => Status::HIDDEN]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/sales-channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $channel->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow(string $user): void
    {
        $channel = SalesChannel::factory()->create(['status' => Status::HIDDEN]);

        $this->{$user}->givePermissionTo('sales_channels.show_hidden');
        $this
            ->actingAs($this->{$user})
            ->json('GET', "/sales-channels/id:{$channel->getKey()}")
            ->assertOk()
            ->assertJsonFragment(['id' => $channel->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        /** @var Language $lang */
        $lang = Language::default();

        $this->{$user}->givePermissionTo('sales_channels.add');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/sales-channels', [
                'id' => $id = Str::uuid(),
                'translations' => [
                    $lang->getKey() => [
                        'name' => 'Test',
                    ],
                ],
                'status' => Status::ACTIVE->value,
                'vat_rate' => '23',
                'slug' => 'test',
                'countries_block_list' => false,
                'default_currency' => Currency::DEFAULT,
                'default_language_id' => $lang->getKey(),
                'countries' => ['pl', 'us'],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('sales_channels', [
            'id' => $id,
            "name->{$lang->getKey()}" => 'Test',
            'vat_rate' => '23',
        ]);

        $this->assertDatabaseHas('sales_channels_countries', [
            'sales_channel_id' => $id,
            'country_code' => 'pl',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithEmptyCountriesArray(string $user): void
    {
        /** @var Language $lang */
        $lang = Language::default();

        $this->{$user}->givePermissionTo('sales_channels.add');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/sales-channels', [
                'id' => $id = Str::uuid(),
                'translations' => [
                    $lang->getKey() => [
                        'name' => 'Test',
                    ],
                ],
                'status' => Status::ACTIVE->value,
                'vat_rate' => '23',
                'slug' => 'test',
                'countries_block_list' => false,
                'default_currency' => Currency::DEFAULT,
                'default_language_id' => $lang->getKey(),
                'countries' => [],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('sales_channels', [
            'id' => $id,
            "name->{$lang->getKey()}" => 'Test',
            'vat_rate' => '23',
        ]);

        $this->assertDatabaseMissing('sales_channels_countries', [
            'sales_channel_id' => $id,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $channel = SalesChannel::factory()->create(['vat_rate' => '23']);

        $this->{$user}->givePermissionTo('sales_channels.edit');
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/sales-channels/id:{$channel->getKey()}", [
                'vat_rate' => '20',
            ])
            ->assertOk();

        $this->assertDatabaseHas('sales_channels', [
            'id' => $channel->getKey(),
            'vat_rate' => '20',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $channel = SalesChannel::factory()->create();

        $this->{$user}->givePermissionTo('sales_channels.remove');
        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/sales-channels/id:{$channel->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('sales_channels', [
            'id' => $channel->getKey(),
        ]);
    }
}
