<?php

namespace Tests\Feature\Organizations;

use App\Enums\ValidationError;
use App\Models\Address;
use Domain\Organization\Enums\OrganizationStatus;
use Domain\Organization\Models\Organization;
use Domain\SalesChannel\Models\SalesChannel;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    private Organization $organization;
    private Address $address;

    public function setUp(): void
    {
        parent::setUp();

        $this->address = Address::factory()->create();

        $this->organization = Organization::factory()->create([
            'address_id' => $this->address->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $this->json('GET', '/organizations')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.show');

        Organization::factory()->count(10)->create([
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        $this->actingAs($this->{$user})->json('GET', '/organizations')->assertOk()->assertJsonCount(11, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByStatus(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.show');

        $this->organization->update([
            'status' => OrganizationStatus::VERIFIED,
        ]);

        Organization::factory()->count(10)->create([
            'status' => OrganizationStatus::UNVERIFIED,
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        $this->actingAs($this->{$user})->json('GET', '/organizations', ['status' => OrganizationStatus::VERIFIED->value])->assertOk()->assertJsonCount(1, 'data');
    }

    public function testShowUnauthorized(): void
    {
        $this->json('GET', '/organizations/id:' . $this->organization->getKey())->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.show_details');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/organizations/id:' . $this->organization->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->organization->getKey(),
                'name' => $this->organization->name,
                'description' => $this->organization->description,
                'phone' => $this->organization->phone,
                'address' => [
                    'id' => $this->address->getKey(),
                    'name' => $this->address->name,
                    'address' => $this->address->address,
                    'city' => $this->address->city,
                    'country' => $this->address->country,
                    'country_name' => $this->address->country_name,
                    'phone' => $this->address->phone,
                    'vat' => $this->address->vat,
                    'zip' => $this->address->zip,
                ],
                'email' => $this->organization->email,
                'assistants' => [],
                'users' => [],
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        $address = Address::factory()->definition();

        $this
            ->json('POST', '/organizations', [
                'name' => 'organization',
                'phone' => '+48123321123',
                'email' => 'test@test.test',
                'address' => $address,
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.add');

        $address = Address::factory()->definition();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations', [
                'name' => 'organization',
                'phone' => '+48123321123',
                'email' => 'test@test.test',
                'address' => $address,
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'organization',
                'phone' => '+48123321123',
                'email' => 'test@test.test',
                'status' => OrganizationStatus::UNVERIFIED->value,
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'organization',
            'phone' => '+48123321123',
            'email' => 'test@test.test',
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this
            ->json('PATCH', '/organizations/id:' . $this->organization->getKey(), [
                'name' => 'New name',
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/organizations/id:' . $this->organization->getKey(), [
                'name' => 'New name',
            ])
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSalesChannelNoPermission(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $salesChannel = SalesChannel::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/organizations/id:' . $this->organization->getKey(), [
                'sales_channel_id' => $salesChannel->getKey(),
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::PROHIBITED,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSalesChannel(string $user): void
    {
        $this->{$user}->givePermissionTo(['organizations.edit', 'organizations.verify']);

        $salesChannel = SalesChannel::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/organizations/id:' . $this->organization->getKey(), [
                'sales_channel_id' => $salesChannel->getKey(),
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $salesChannel->getKey(),
                'name' => $salesChannel->name,
            ]);
    }

    public function testRemoveUnauthorized(): void
    {
        $this
            ->json('DELETE', '/organizations/id:' . $this->organization->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testRemove(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.remove');

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', '/organizations/id:' . $this->organization->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('organizations', [
            'id' => $this->organization->getKey(),
            'name' => $this->organization->name,
        ]);
    }
}
