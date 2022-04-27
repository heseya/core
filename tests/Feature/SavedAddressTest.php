<?php

namespace Tests\Feature;

use App\Enums\SavedAddressType;
use App\Models\Address;
use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedAddressTest extends TestCase
{
    use RefreshDatabase;

    private Address $address;
    private User $fakeUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->address = Address::factory()->create();
        $this->fakeUser = User::factory()->create();
    }

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/auth/profile/delivery-addresses', [
            'name' => 'test',
            'default' => false,
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::DELIVERY,
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        $response = $this->actingAs($this->$user)->postJson('/auth/profile/delivery-addresses', [
            'name' => 'test',
            'default' => false,
            'type' => SavedAddressType::DELIVERY,
            'address' => [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ]);

        $savedAddress = SavedAddress::where('name', 'test')->with('address')->first();

        $response->assertOk();

        $this->assertDatabaseHas('saved_addresses', [
            'name' => 'test',
            'default' => 0,
            'type' => SavedAddressType::DELIVERY,
            'address_id' => $savedAddress->address->getKey(),
        ])
            ->assertDatabaseHas('addresses', [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testNewDefault($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        SavedAddress::create([
            'name' => 'test',
            'default' => true,
            'user_id' => $this->$user->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::DELIVERY,
        ]);

        $this->actingAs($this->$user)->postJson('/auth/profile/delivery-addresses', [
            'name' => 'test2',
            'default' => true,
            'type' => SavedAddressType::DELIVERY,
            'address' => [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])
            ->assertOk();

        $savedAddress = SavedAddress::where('name', 'test2')->first();

        $this
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test',
                'default' => 0,
                'type' => SavedAddressType::DELIVERY,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test2',
                'default' => 1,
                'type' => SavedAddressType::DELIVERY,
                'address_id' => $savedAddress->address->getKey(),
            ])
            ->assertDatabaseHas('addresses', [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this->postJson('/auth/profile/delivery-addresses', [
            'name' => 'test',
            'default' => false,
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::DELIVERY,
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'name' => 'test',
            'default' => false,
            'user_id' => $this->$user->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::DELIVERY,
        ]);

        $this->actingAs($this->$user)
            ->patchJson('/auth/profile/delivery-addresses/id:' . $savedAddress->getKey(), [
                'name' => 'test2',
                'default' => true,
                'type' => SavedAddressType::DELIVERY,
                'address' => [
                    'name' => 'test',
                    'phone' => '123456789',
                    'address' => 'testest',
                    'zip' => '123',
                    'city' => 'testcity',
                    'country' => 'ts',
                    'vat' => '10',
                ],
            ]);

        $this
            ->assertDatabaseMissing('saved_addresses', [
                'name' => 'test',
                'default' => false,
                'address_id' => $this->address->getKey(),
                'type' => SavedAddressType::DELIVERY,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test2',
                'default' => true,
                'address_id' => $savedAddress->address->getKey(),
                'type' => SavedAddressType::DELIVERY,
            ])
            ->assertDatabaseHas('addresses', [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeDefault($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        SavedAddress::create([
            'name' => 'test1',
            'default' => true,
            'user_id' => $this->$user->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::DELIVERY,
        ]);

        $this->actingAs($this->$user)->postJson('/auth/profile/delivery-addresses', [
            'name' => 'test2',
            'default' => false,
            'type' => SavedAddressType::DELIVERY,
            'address' => [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ]);

        $savedAddress = SavedAddress::where([
            'name' => 'test2',
            'user_id' => $this->$user->getKey(),
        ])
            ->first();

        $this->actingAs($this->$user)
            ->patchJson('/auth/profile/delivery-addresses/id:' . $savedAddress->getKey(), [
                'name' => 'test2',
                'default' => true,
                'type' => SavedAddressType::DELIVERY,
                'address' => [
                    'name' => 'test2',
                    'phone' => '987654321',
                    'address' => 'tsettset',
                    'zip' => '321',
                    'city' => 'citytest',
                    'country' => 'st',
                    'vat' => '15',
                ],
            ]);

        $this
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test2',
                'default' => 1,
                'type' => SavedAddressType::DELIVERY,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test1',
                'default' => 0,
                'type' => SavedAddressType::DELIVERY,
            ])
            ->assertDatabaseHas('addresses', [
                'name' => 'test2',
                'phone' => '987654321',
                'address' => 'tsettset',
                'zip' => '321',
                'city' => 'citytest',
                'country' => 'st',
                'vat' => '15',
            ])
            ->assertDatabaseMissing('addresses', [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOtherUserSavedAddress($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'default' => 0,
            'name' => 'test',
            'type' => SavedAddressType::DELIVERY,
            'address_id' => $this->address->getKey(),
            'user_id' => $this->fakeUser->getKey(),
        ]);

        $this->actingAs($this->$user)->patchJson('/auth/profile/delivery-addresses/id:' . $savedAddress->getKey(), [
            'name' => 'test2',
            'default' => false,
            'type' => SavedAddressType::DELIVERY,
            'address' => [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])
            ->assertUnauthorized();
    }

    public function testDeleteUnauthorized(): void
    {
        $savedAddress = SavedAddress::create([
            'default' => 0,
            'name' => 'test',
            'type' => SavedAddressType::DELIVERY,
            'address_id' => $this->address->getKey(),
            'user_id' => $this->fakeUser->getKey(),
        ]);

        $this->deleteJson('/auth/profile/delivery-addresses/id:' . $savedAddress->getKey())->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'name' => 'test',
            'default' => false,
            'user_id' => $this->$user->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::DELIVERY,
        ]);

        $this->actingAs($this->$user)
            ->deleteJson('/auth/profile/delivery-addresses/id:' . $savedAddress->getKey());

        $this->assertDatabaseMissing('saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDefault($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'name' => 'test',
            'default' => true,
            'user_id' => $this->$user->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::DELIVERY,
        ]);

        $this->actingAs($this->$user)
            ->deleteJson('/auth/profile/delivery-addresses/id:' . $savedAddress->getKey())
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'You cannot delete default address']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProfileHasDefaultDeliveryAndInvoiceAddresses($user): void
    {
        $this->$user->givePermissionTo('profile.addresses_manage');

        $this->actingAs($this->$user)->postJson('/auth/profile/delivery-addresses', [
            'name' => 'test',
            'default' => true,
            'type' => SavedAddressType::DELIVERY,
            'address' => [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ]);

        $this->actingAs($this->$user)->postJson('/auth/profile/invoice-addresses', [
            'name' => 'test2',
            'default' => true,
            'type' => SavedAddressType::INVOICE,
            'address' => [
                'name' => 'test',
                'phone' => '123456789',
                'address' => 'testest',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ]);

        $response = $this->actingAs($this->$user)->getJson('/auth/profile');

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'delivery_addresses',
                'invoice_addresses',
            ],
        ]);

        $this
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test',
                'default' => 1,
                'type' => SavedAddressType::DELIVERY,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test2',
                'default' => 1,
                'type' => SavedAddressType::INVOICE,
            ]);
    }
}