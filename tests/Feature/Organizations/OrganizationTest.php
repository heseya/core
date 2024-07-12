<?php

namespace Tests\Feature\Organizations;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\ValidationError;
use App\Models\Address;
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
            'billing_address_id' => $this->address->getKey(),
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
                'billing_address' => [
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
                'billing_email' => $this->organization->billing_email,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowByClientId(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.show_details');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/organizations/' . $this->organization->client_id)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->organization->getKey(),
                'billing_address' => [
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
                'billing_email' => $this->organization->billing_email,
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        $address = Address::factory()->definition();

        $this
            ->json('POST', '/organizations', [
                'billing_email' => 'test@test.test',
                'billing_address' => $address,
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
        $address['vat'] = '123456789';
        $shippingAddress = Address::factory()->definition();

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations', [
                'client_id' => 'CLIENT_01',
                'billing_email' => 'test@test.test',
                'billing_address' => $address,
                'shipping_addresses' => [
                    [
                        'default' => false,
                        'name' => 'Shipping address',
                        'address' => $shippingAddress,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'billing_email' => 'test@test.test',
                'client_id' => 'CLIENT_01',
            ])
            ->assertJsonFragment($address);

        $this->assertDatabaseHas('organizations', [
            'billing_email' => 'test@test.test',
            'client_id' => 'CLIENT_01',
        ]);

        $this->assertDatabaseHas('addresses', $address);
        $this->assertDatabaseHas('addresses', $shippingAddress);

        $organization = Organization::query()->where('id', '=', $response->getData()->data->id)->first();

        $this->assertDatabaseHas('organization_saved_addresses', [
            'organization_id' => $organization->getKey(),
            'default' => true,
            'name' => 'Shipping address',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateExistingVat(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.add');

        $address = Address::factory()->definition();
        $address['vat'] = '123456789';

        $existingAddress = Address::create($address);
        Organization::factory()->create([
            'billing_address_id' => $existingAddress->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations', [
                'billing_email' => 'test@test.test',
                'billing_address' => $address,
                'shipping_addresses' => [
                    [
                        'default' => true,
                        'name' => 'Shipping address',
                        'address' => $address,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::ORGANIZATIONUNIQUEVAT->value,
                'message' => Exceptions::CLIENT_ORGANIZATION_EXIST->value,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinimumShippingAddresses(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.add');

        $address = Address::factory()->definition();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations', [
                'billing_email' => 'test@test.test',
                'billing_address' => $address,
                'shipping_addresses' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'The shipping addresses must have at least 1 items.',
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this
            ->json('PATCH', '/organizations/id:' . $this->organization->getKey(), [
                'billing_email' => 'new.email@test.com',
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
                'billing_email' => 'new.email@test.com',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->organization->getKey(),
                'billing_email' => 'new.email@test.com',
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
        ]);
    }
}
