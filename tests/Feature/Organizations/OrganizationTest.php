<?php

namespace Tests\Feature\Organizations;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\ValidationError;
use App\Models\Address;
use App\Models\Role;
use App\Models\User;
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

        $this->address = Address::factory()->create([
            'vat' => '123456789',
        ]);

        $this->organization = Organization::factory()->create([
            'change_version' => 0,
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

    /**
     * @dataProvider authProvider
     */
    public function testIndexIsComplete(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.show');

        $completeOrganization = Organization::factory()->create([
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'is_complete' => true,
        ]);

        $incompleteOrganization = Organization::factory()->create([
            'is_complete' => false,
        ]);

        $this->actingAs($this->{$user})
            ->json('GET', '/organizations', ['is_complete' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $completeOrganization->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $incompleteOrganization->getKey(),
            ]);
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
        $address['vat'] = '987654321';
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
            'is_complete' => false,
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
                'billing_address' => [
                    'name' => $this->address->name,
                    'address' => $this->address->address,
                    'city' => $this->address->city,
                    'country' => $this->address->country,
                    'country_name' => $this->address->country_name,
                    'phone' => $this->address->phone,
                    'vat' => $this->address->vat,
                    'zip' => $this->address->zip,
                ]
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->organization->getKey(),
                'billing_email' => 'new.email@test.com',
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->getKey(),
            'change_version' => 1,
            'is_complete' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSalesChannel(string $user): void
    {
        $this->{$user}->givePermissionTo(['organizations.edit']);

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

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->getKey(),
            'change_version' => 1,
            'is_complete' => true,
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

    public function testRegisterUnauthorized(): void
    {
        $address = Address::factory()->definition();
        $address['vat'] = '321456987';

        $this
            ->json('POST', '/organizations/register', [
                'billing_email' => 'test.organization@example.com',
                'billing_address' => $address,
                'shipping_addresses' => [
                    'default' => true,
                    'name' =>  'Shipping address',
                    'address' => $address,
                ],
                'creator_email' => 'creator@example.com',
                'creator_password' => 'Test123!',
                'creator_name' => 'Jan Kowalski',
            ])
            ->assertForbidden();
    }

    public function testRegister(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.organization_register');

        $address = Address::factory()->definition();
        $address['vat'] = '321456987';

        $response = $this
            ->json('POST', '/organizations/register', [
                'billing_email' => 'test.organization@example.com',
                'billing_address' => $address,
                'shipping_addresses' => [
                    [
                        'default' => true,
                        'name' =>  'Shipping address',
                        'address' => $address,
                    ],
                ],
                'creator_email' => 'creator@example.com',
                'creator_password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
                'creator_name' => 'Jan Kowalski',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('organizations', [
            'billing_email' => 'test.organization@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Jan Kowalski',
            'email' => 'creator@example.com',
        ]);

        /** @var User $user */
        $user = User::query()->where('email', '=', 'creator@example.com')->first();

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $user->getKey(),
            'organization_id' => $response->getData()->data->id,
        ]);
    }

    public function testRegisterExistingVat(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.organization_register');

        $address = Address::factory()->definition();
        $address['vat'] = '123456789';

        $existingAddress = Address::create($address);
        Organization::factory()->create([
            'billing_address_id' => $existingAddress->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        $this
            ->json('POST', '/organizations/register', [
                'billing_email' => 'test.organization@example.com',
                'billing_address' => $address,
                'shipping_addresses' => [
                    [
                        'default' => true,
                        'name' =>  'Shipping address',
                        'address' => $address,
                    ],
                ],
                'creator_email' => 'creator@example.com',
                'creator_password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
                'creator_name' => 'Jan Kowalski',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::ORGANIZATIONUNIQUEVAT->value,
                'message' => Exceptions::CLIENT_ORGANIZATION_EXIST->value,
            ]);
    }

    public function testRegisterMinimumShippingAddresses(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.organization_register');

        $address = Address::factory()->definition();

        $this
            ->json('POST', '/organizations/register', [
                'billing_email' => 'test@test.test',
                'billing_address' => $address,
                'shipping_addresses' => [],
                'creator_email' => 'creator@example.com',
                'creator_password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
                'creator_name' => 'Jan Kowalski',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'The shipping addresses must have at least 1 items.',
            ]);
    }
}
