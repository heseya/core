<?php

declare(strict_types=1);

namespace Tests\Feature\SalesChannels;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\ValidationError;
use App\Models\PaymentMethod;
use Domain\Language\Language;
use Domain\Organization\Models\Organization;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Support\Str;
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
        SalesChannel::factory()->create(['status' => SalesChannelStatus::PRIVATE]);

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
    public function testIndexHidden(string $user): void
    {
        $this->{$user}->givePermissionTo('sales_channels.show_hidden');

        /** @var SalesChannel $channel */
        $channel = SalesChannel::query()->first(); // default channel from migration
        $hidden = SalesChannel::factory()->create(['status' => SalesChannelStatus::PRIVATE, 'published' => [$this->lang]]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/sales-channels')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $channel->getKey()])
            ->assertJsonFragment(['id' => $hidden->getKey()]);
    }

    public function testIndexHiddenUserInOrganization(): void
    {
        /** @var SalesChannel $channel */
        $channel = SalesChannel::query()->first(); // default channel from migration
        $hidden = SalesChannel::factory()->create(['status' => SalesChannelStatus::PRIVATE, 'published' => [$this->lang]]);
        $hiddenInOrganization = SalesChannel::factory()->create(['status' => SalesChannelStatus::PRIVATE, 'published' => [$this->lang]]);
        $organization = Organization::factory()->create(['sales_channel_id' => $hiddenInOrganization]);
        $this->user->organizations()->attach($organization->getKey());

        $this
            ->actingAs($this->user)
            ->json('GET', '/sales-channels')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $channel->getKey()])
            ->assertJsonFragment(['id' => $hiddenInOrganization->getKey()])
            ->assertJsonMissing(['id' => $hidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow(string $user): void
    {
        $channel = SalesChannel::factory()->create(['status' => SalesChannelStatus::PUBLIC]);

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

        PaymentMethod::factory()->count(5)->create();
        ShippingMethod::factory()->count(5)->create();

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
                'status' => SalesChannelStatus::PUBLIC->value,
                'activity' => SalesChannelActivityType::ACTIVE->value,
                'vat_rate' => '23',
                'slug' => 'test',
                'language_id' => $lang->getKey(),
                'default' => false,
                'shipping_method_ids' => [],
                'payment_method_ids' => [],
                // TODO replace with real price map
                'price_map_id' => Str::uuid()->toString(),
            ])
            ->assertCreated()
            ->assertJsonCount(5, 'data.shipping_methods')
            ->assertJsonCount(5, 'data.payment_methods');

        $this->assertDatabaseHas('sales_channels', [
            'id' => $id,
            "name->{$lang->getKey()}" => 'Test',
            'vat_rate' => '23',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateDefaultPrivate(string $user): void
    {
        /** @var Language $lang */
        $lang = Language::default();

        PaymentMethod::factory()->count(5)->create();
        ShippingMethod::factory()->count(5)->create();

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
                'status' => SalesChannelStatus::PRIVATE->value,
                'activity' => SalesChannelActivityType::INACTIVE->value,
                'vat_rate' => '23',
                'slug' => 'test',
                'language_id' => $lang->getKey(),
                'default' => true,
                'shipping_method_ids' => [],
                'payment_method_ids' => [],
                // TODO replace with real price map
                'price_map_id' => Str::uuid()->toString(),
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::SALESCHANNELDEFAULT->value,
                'message' => Exceptions::CLIENT_SALES_CHANNEL_DEFAULT_ACTIVE_AND_PUBLIC->value,
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
                'default' => false,
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
    public function testUpdateNewDefault(string $user): void
    {
        $defaultChannel = SalesChannel::query()->where('default', '=', true)->firstOrCreate([
            'status' => SalesChannelStatus::PUBLIC->value,
            'activity' => SalesChannelActivityType::ACTIVE->value,
        ]);
        $channel = SalesChannel::factory()->create(['status' => SalesChannelStatus::PUBLIC, 'activity' => SalesChannelActivityType::ACTIVE]);

        $this->{$user}->givePermissionTo('sales_channels.edit');
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/sales-channels/id:{$channel->getKey()}", [
                'default' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('sales_channels', [
            'id' => $channel->getKey(),
            'default' => true,
        ]);

        $this->assertDatabaseHas('sales_channels', [
            'id' => $defaultChannel->getKey(),
            'default' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateActivityOrganization(string $user): void
    {
        $channel = SalesChannel::factory()->create(['activity' => SalesChannelActivityType::ACTIVE]);
        Organization::factory()->create(['sales_channel_id' => $channel->getKey()]);

        $this->{$user}->givePermissionTo('sales_channels.edit');
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/sales-channels/id:{$channel->getKey()}", [
                'activity' => SalesChannelActivityType::INACTIVE->value,
                'default' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::SALESCHANNELACTIVITYORGANIZATION->value,
                'message' => Exceptions::CLIENT_SALES_CHANNEL_ORGANIZATION_ACTIVE->value,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDefaultInactive(string $user): void
    {
        $channel = SalesChannel::factory()
            ->create(['activity' => SalesChannelActivityType::ACTIVE, 'status' => SalesChannelStatus::PUBLIC]);

        $this->{$user}->givePermissionTo('sales_channels.edit');
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/sales-channels/id:{$channel->getKey()}", [
                'activity' => SalesChannelActivityType::INACTIVE->value,
                'default' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::SALESCHANNELDEFAULT->value,
                'message' => Exceptions::CLIENT_SALES_CHANNEL_DEFAULT_ACTIVE_AND_PUBLIC->value,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDefaultPrivate(string $user): void
    {
        $channel = SalesChannel::factory()
            ->create(['activity' => SalesChannelActivityType::ACTIVE, 'status' => SalesChannelStatus::PUBLIC]);

        $this->{$user}->givePermissionTo('sales_channels.edit');
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/sales-channels/id:{$channel->getKey()}", [
                'status' => SalesChannelStatus::PRIVATE->value,
                'default' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::SALESCHANNELDEFAULT->value,
                'message' => Exceptions::CLIENT_SALES_CHANNEL_DEFAULT_ACTIVE_AND_PUBLIC->value,
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

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDefault(string $user): void
    {
        $channel = SalesChannel::factory()->create(['default' => true]);

        $this->{$user}->givePermissionTo('sales_channels.remove');
        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/sales-channels/id:{$channel->getKey()}")
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::CLIENT_SALES_CHANNEL_DEFAULT_DELETE->value,
                'key' => Exceptions::CLIENT_SALES_CHANNEL_DEFAULT_DELETE->name,
            ]);
    }
}
