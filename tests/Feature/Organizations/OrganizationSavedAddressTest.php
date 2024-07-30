<?php

namespace Tests\Feature\Organizations;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\ValidationError;
use App\Models\Address;
use Domain\Organization\Models\Organization;
use Domain\Organization\Models\OrganizationSavedAddress;
use Domain\SalesChannel\Models\SalesChannel;
use Tests\TestCase;

class OrganizationSavedAddressTest extends TestCase
{
    private Organization $organization;
    private Address $address;
    private array $addressData;
    private OrganizationSavedAddress $savedAddress;

    public function setUp(): void
    {
        parent::setUp();

        $this->addressData = Address::factory()->definition();
        $this->addressData['vat'] = '123456789';

        $this->address = Address::factory()->create($this->addressData);

        $this->organization = Organization::factory()->create([
            'change_version' => 0,
            'billing_address_id' => $this->address->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        $this->savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => true,
            'address_id' => $this->address->getKey(),
            'organization_id' => $this->organization->getKey(),
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $this
            ->json('GET', "/organizations/id:{$this->organization->getKey()}/shipping-addresses")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $address = Address::factory()->create();
        OrganizationSavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'default' => false,
        ]);
        OrganizationSavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'default' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/organizations/id:{$this->organization->getKey()}/shipping-addresses")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function testCreateUnauthorized(): void
    {
        $this
            ->json('POST', "/organizations/id:{$this->organization->getKey()}/shipping-addresses", [
                'default' => true,
                'name' => 'Shipping address',
                'address' => Address::factory()->definition(),
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $this
            ->actingAs($this->{$user})
            ->json('POST', "/organizations/id:{$this->organization->getKey()}/shipping-addresses", [
                'default' => true,
                'name' => 'Shipping address',
                'address' => Address::factory()->definition(),
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'default' => true,
                'name' => 'Shipping address'
            ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'default' => true,
            'name' => 'Shipping address',
            'change_version' => 0,
        ]);

        $this->assertDatabaseMissing('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => true,
        ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateNoNameAndCompanyName(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $address = Address::factory()->definition();
        $address['name'] = null;
        $address['company_name'] = null;

        $this
            ->actingAs($this->{$user})
            ->json('POST', "/organizations/id:{$this->organization->getKey()}/shipping-addresses", [
                'default' => true,
                'name' => 'Shipping address',
                'address' => $address,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'The address.name field is required when address.company name is not present.'
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this
            ->json('PATCH', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$this->savedAddress->getKey()}", [
                'name' => 'Shipping address',
                'address' => Address::factory()->definition(),
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
            ->json('PATCH', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$this->savedAddress->getKey()}", [
                'default' => true,
                'name' => 'New name',
                'address' => $this->addressData,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->savedAddress->getKey(),
                'name' => 'New name',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDefault(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$this->savedAddress->getKey()}", [
                'default' => false,
                'name' => $this->savedAddress->name,
                'address' => $this->addressData,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::ORGANIZATIONSAVEDADDRESSDEFAULT->value,
                'message' => Exceptions::CLIENT_ORGANIZATION_ADDRESS_DEFAULT->value,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNewDefault(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $address = Address::factory()->create();

        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'change_version' => 0,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$savedAddress->getKey()}", [
                'default' => true,
                'name' => $savedAddress->name,
                'address' => $this->addressData,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $savedAddress->getKey(),
                'default' => true,
            ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $savedAddress->getKey(),
            'change_version' => 1,
        ]);

        $this->assertDatabaseMissing('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => true,
        ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDifferentOrganization(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $address = Address::factory()->create();

        $newOrganization = Organization::factory()->create([
            'change_version' => 0,
            'billing_address_id' => $address->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        /** @var OrganizationSavedAddress $savedAddress */
        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $newOrganization->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$savedAddress->getKey()}", [
                'default' => true,
                'name' => $savedAddress->name,
                'address' => $this->addressData,
            ])
            ->assertNotFound();
    }

    public function testDeleteUnauthorized(): void
    {
        $this
            ->json('DELETE', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$this->savedAddress->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $address = Address::factory()->create();

        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'change_version' => 0,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$savedAddress->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('organization_saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDefault(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.edit');

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/organizations/id:{$this->organization->getKey()}/shipping-addresses/id:{$this->savedAddress->getKey()}")
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::CLIENT_ORGANIZATION_ADDRESS_REMOVE_DEFAULT->value,
            ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
        ]);
    }

    public function testIndexMy(): void
    {
        $this->user->givePermissionTo('organizations.edit');
        $this->user->organizations()->attach($this->organization->getKey());

        $address = Address::factory()->create();
        OrganizationSavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'default' => false,
        ]);
        OrganizationSavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'default' => false,
        ]);

        $newOrganization = Organization::factory()->create([
            'change_version' => 0,
            'billing_address_id' => $address->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        /** @var OrganizationSavedAddress $savedAddress */
        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $newOrganization->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', "/my/organization/shipping-addresses")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing([
                'id' => $savedAddress->getKey(),
            ]);
    }

    public function testCreateMy(): void
    {
        $this->user->givePermissionTo('organizations.edit');
        $this->user->organizations()->attach($this->organization->getKey());

        $this
            ->actingAs($this->user)
            ->json('POST', "/my/organization/shipping-addresses", [
                'default' => true,
                'name' => 'Shipping address',
                'address' => Address::factory()->definition(),
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'default' => true,
                'name' => 'Shipping address'
            ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'default' => true,
            'name' => 'Shipping address',
            'change_version' => 0,
        ]);

        $this->assertDatabaseMissing('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => true,
        ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => false,
        ]);
    }

    public function testUpdateMy(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        $this->user->organizations()->attach($this->organization->getKey());

        $this
            ->actingAs($this->user)
            ->json('PATCH', "/my/organization/shipping-addresses/id:{$this->savedAddress->getKey()}", [
                'default' => true,
                'name' => 'New name',
                'address' => $this->addressData,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->savedAddress->getKey(),
                'name' => 'New name',
            ]);
    }

    public function testUpdateMyDefault(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        $this->user->organizations()->attach($this->organization->getKey());

        $this
            ->actingAs($this->user)
            ->json('PATCH', "/my/organization/shipping-addresses/id:{$this->savedAddress->getKey()}", [
                'default' => false,
                'name' => $this->savedAddress->name,
                'address' => $this->addressData,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::ORGANIZATIONSAVEDADDRESSDEFAULT->value,
                'message' => Exceptions::CLIENT_ORGANIZATION_ADDRESS_DEFAULT->value,
            ]);
    }

    public function testUpdateMyNewDefault(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        $this->user->organizations()->attach($this->organization->getKey());

        $address = Address::factory()->create();

        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'change_version' => 0,
        ]);

        $this
            ->actingAs($this->user)
            ->json('PATCH', "/my/organization/shipping-addresses/id:{$savedAddress->getKey()}", [
                'default' => true,
                'name' => $savedAddress->name,
                'address' => $this->addressData,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $savedAddress->getKey(),
                'default' => true,
            ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $savedAddress->getKey(),
            'change_version' => 1,
        ]);

        $this->assertDatabaseMissing('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => true,
        ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
            'default' => false,
        ]);
    }

    public function testUpdateMyDifferentOrganization(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        $this->user->organizations()->attach($this->organization->getKey());

        $address = Address::factory()->create();

        $newOrganization = Organization::factory()->create([
            'change_version' => 0,
            'billing_address_id' => $address->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        /** @var OrganizationSavedAddress $savedAddress */
        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $newOrganization->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('PATCH', "/my/organization/shipping-addresses/id:{$savedAddress->getKey()}", [
                'default' => true,
                'name' => $savedAddress->name,
                'address' => $this->addressData,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_ORGANIZATION_INVALID_ADDRESS->name,
                'message' => Exceptions::CLIENT_ORGANIZATION_INVALID_ADDRESS->value,
            ]);
    }

    public function testDeleteMy(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        $this->user->organizations()->attach($this->organization->getKey());

        $address = Address::factory()->create();

        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $this->organization->getKey(),
            'change_version' => 0,
        ]);

        $this
            ->actingAs($this->user)
            ->json('DELETE', "/my/organization/shipping-addresses/id:{$savedAddress->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('organization_saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
    }

    public function testDeleteMyDefault(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        $this->user->organizations()->attach($this->organization->getKey());

        $this
            ->actingAs($this->user)
            ->json('DELETE', "/my/organization/shipping-addresses/id:{$this->savedAddress->getKey()}")
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::CLIENT_ORGANIZATION_ADDRESS_REMOVE_DEFAULT->value,
            ]);

        $this->assertDatabaseHas('organization_saved_addresses', [
            'id' => $this->savedAddress->getKey(),
        ]);
    }

    public function testDeleteMyDifferentOrganization(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        $this->user->organizations()->attach($this->organization->getKey());

        $address = Address::factory()->create();

        $newOrganization = Organization::factory()->create([
            'change_version' => 0,
            'billing_address_id' => $address->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        /** @var OrganizationSavedAddress $savedAddress */
        $savedAddress = OrganizationSavedAddress::factory()->create([
            'default' => false,
            'address_id' => $address->getKey(),
            'organization_id' => $newOrganization->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('DELETE', "/my/organization/shipping-addresses/id:{$savedAddress->getKey()}")
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_ORGANIZATION_INVALID_ADDRESS->name,
                'message' => Exceptions::CLIENT_ORGANIZATION_INVALID_ADDRESS->value,
            ]);
    }
}
