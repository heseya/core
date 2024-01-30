<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
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
        $this->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => false,
            'address_id' => $this->address->getKey(),
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        $response = $this->actingAs($this->{$user})->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => false,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
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
            'type' => SavedAddressType::SHIPPING,
            'address_id' => $savedAddress->address->getKey(),
        ])
            ->assertDatabaseHas('addresses', [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateNonLatinDefaultWithEmptyVat(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');


        $response = $this->actingAs($this->{$user})->postJson('/auth/profile/shipping-addresses', [
            'name' => 'default',
            'default' => true,
            'address' => [
                'name' => 'Коваленко Коваленко',
                'phone' => '123123123',
                'address' => 'Коваленко 22',
                'zip' => '22-333',
                'city' => 'Коваленко',
                'country' => 'PL',
                'country_name' => 'Polska',
                'vat' => '',
            ],
        ]);
        $response->assertOk();

        $savedAddress = SavedAddress::where('name', 'default')->with('address')->first();
        $this->assertDatabaseHas('saved_addresses', [
            'name' => 'default',
            'default' => 1,
            'type' => SavedAddressType::SHIPPING,
            'address_id' => $savedAddress->address->getKey(),
        ])
            ->assertDatabaseHas('addresses', [
                'name' => 'Коваленко Коваленко',
                'phone' => '123123123',
                'address' => 'Коваленко 22',
                'zip' => '22-333',
                'city' => 'Коваленко',
                'country' => 'PL',
                'vat' => null,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testNewDefault(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        SavedAddress::create([
            'name' => 'test',
            'default' => true,
            'user_id' => $this->{$user}->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);

        $this->actingAs($this->{$user})->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test2',
            'default' => true,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
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
                'type' => SavedAddressType::SHIPPING,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test2',
                'default' => 1,
                'type' => SavedAddressType::SHIPPING,
                'address_id' => $savedAddress->address->getKey(),
            ])
            ->assertDatabaseHas('addresses', [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => false,
            'address_id' => $this->address->getKey(),
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'name' => 'test',
            'default' => false,
            'user_id' => $this->{$user}->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);

        $this->actingAs($this->{$user})
            ->patchJson('/auth/profile/shipping-addresses/id:' . $savedAddress->getKey(), [
                'name' => 'test2',
                'default' => true,
                'address' => [
                    'name' => 'Jan Nowak',
                    'phone' => '123456789',
                    'address' => 'Testowa 12',
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
                'type' => SavedAddressType::SHIPPING,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test2',
                'default' => true,
                'address_id' => $savedAddress->address->getKey(),
                'type' => SavedAddressType::SHIPPING,
            ])
            ->assertDatabaseHas('addresses', [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeDefault(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        SavedAddress::create([
            'name' => 'test1',
            'default' => true,
            'user_id' => $this->{$user}->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);

        $this->actingAs($this->{$user})->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test2',
            'default' => false,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ]);

        $savedAddress = SavedAddress::where([
            'name' => 'test2',
            'user_id' => $this->{$user}->getKey(),
        ])
            ->first();

        $this->actingAs($this->{$user})
            ->patchJson('/auth/profile/shipping-addresses/id:' . $savedAddress->getKey(), [
                'name' => 'test2',
                'default' => true,
                'address' => [
                    'name' => 'Jan Nowak',
                    'phone' => '987654321',
                    'address' => 'Testowa 12',
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
                'type' => SavedAddressType::SHIPPING,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test1',
                'default' => 0,
                'type' => SavedAddressType::SHIPPING,
            ])
            ->assertDatabaseHas('addresses', [
                'name' => 'Jan Nowak',
                'phone' => '987654321',
                'address' => 'Testowa 12',
                'zip' => '321',
                'city' => 'citytest',
                'country' => 'st',
                'vat' => '15',
            ])
            ->assertDatabaseMissing('addresses', [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOtherUserSavedAddress(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'default' => 0,
            'name' => 'test',
            'type' => SavedAddressType::SHIPPING,
            'address_id' => $this->address->getKey(),
            'user_id' => $this->fakeUser->getKey(),
        ]);

        $this->actingAs($this->{$user})->patchJson('/auth/profile/shipping-addresses/id:' . $savedAddress->getKey(), [
            'name' => 'test2',
            'default' => false,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])
            ->assertUnauthorized();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateEmptyVat(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::query()->create([
            'name' => 'test',
            'default' => false,
            'user_id' => $this->{$user}->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);

        $this->actingAs($this->{$user})
            ->patchJson('/auth/profile/shipping-addresses/id:' . $savedAddress->getKey(), [
                'name' => 'test2',
                'default' => true,
                'address' => [
                    'name' => 'Jan Nowak',
                    'phone' => '123456789',
                    'address' => 'Testowa 12',
                    'zip' => '123',
                    'city' => 'testcity',
                    'country' => 'ts',
                    'vat' => '',
                ],
            ])
            ->assertOk();

        $this
            ->assertDatabaseHas('addresses', [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => null,
            ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $savedAddress = SavedAddress::create([
            'default' => 0,
            'name' => 'test',
            'type' => SavedAddressType::SHIPPING,
            'address_id' => $this->address->getKey(),
            'user_id' => $this->fakeUser->getKey(),
        ]);

        $this->deleteJson('/auth/profile/shipping-addresses/id:' . $savedAddress->getKey())->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'name' => 'test',
            'default' => false,
            'user_id' => $this->{$user}->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);

        $this->actingAs($this->{$user})
            ->deleteJson('/auth/profile/shipping-addresses/id:' . $savedAddress->getKey());

        $this->assertDatabaseMissing('saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDefault(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        $savedAddress = SavedAddress::create([
            'name' => 'test',
            'default' => true,
            'user_id' => $this->{$user}->getKey(),
            'address_id' => $this->address->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);

        $this->actingAs($this->{$user})
            ->deleteJson('/auth/profile/shipping-addresses/id:' . $savedAddress->getKey())
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'You cannot delete default address']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProfileHasDefaultDeliveryAndInvoiceAddresses(string $user): void
    {
        $this->{$user}->givePermissionTo('profile.addresses_manage');

        $this->actingAs($this->{$user})->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => true,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ]);

        $this->actingAs($this->{$user})->postJson('/auth/profile/billing-addresses', [
            'name' => 'test2',
            'default' => true,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ]);

        $response = $this->actingAs($this->{$user})->getJson('/auth/profile');

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'shipping_addresses',
                'billing_addresses',
            ],
        ]);

        $this
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test',
                'default' => 1,
                'type' => SavedAddressType::SHIPPING,
            ])
            ->assertDatabaseHas('saved_addresses', [
                'name' => 'test2',
                'default' => 1,
                'type' => SavedAddressType::BILLING,
            ]);
    }

    public static function addressesProvider(): array
    {
        return [
            'simple' => ['Krótka 12'],
            'with number' => ['3 Maja 12/12'],
            'with dash' => ['plac Agackiej-Indeckiej 6A'],
            'with apostrophe' => ["Aldridge'a Iry 5"],
            'with dot' => ['al. Wolności 20A/30'],
        ];
    }

    /**
     * @dataProvider addressesProvider
     */
    public function testCreateValidateAddresses(string $address): void
    {
        $this->user->givePermissionTo('profile.addresses_manage');

        $this->actingAs($this->user)->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => false,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => $address,
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])->assertOk();

        $this
            ->assertDatabaseHas('addresses', [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => $address,
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    public static function invalidAddressesProvider(): array
    {
        return [
            'simple' => ['Krótka'],
            'with number' => ['3 Maja'],
        ];
    }

    /**
     * @dataProvider invalidAddressesProvider
     */
    public function testCreateInvalidAddresses(string $address): void
    {
        $this->user->givePermissionTo('profile.addresses_manage');

        $response = $this->actingAs($this->user)->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => false,
            'address' => [
                'name' => 'Jan Nowak',
                'phone' => '123456789',
                'address' => $address,
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::CLIENT_STREET_NUMBER->value,
            ]);
    }

    public static function namesProvider(): array
    {
        return [
            'simple' => ['Jan Nowak'],
            'with dash' => ['Anna Nowak-Kowalska'],
            'with apostrophe' => ["Shas'O Kais"],
            'with more word' => ['Isabella von Carstein'],
        ];
    }

    /**
     * @dataProvider namesProvider
     */
    public function testCreateValidateNames(string $name): void
    {
        $this->user->givePermissionTo('profile.addresses_manage');

        $this->actingAs($this->user)->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => false,
            'address' => [
                'name' => $name,
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])->assertOk();

        $this
            ->assertDatabaseHas('addresses', [
                'name' => $name,
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ]);
    }

    public static function invalidNamesProvider(): array
    {
        return [
            'first name' => ['Jan'],
            'last name' => ['Nowak'],
            'with dash' => ['Nowak-Kowalska'],
            'with apostrophe' => ["Shas'O"],
            'short' => ['j j'],
        ];
    }

    /**
     * @dataProvider invalidNamesProvider
     */
    public function testCreateInvalidNames(string $name): void
    {
        $this->user->givePermissionTo('profile.addresses_manage');

        $this->actingAs($this->user)->postJson('/auth/profile/shipping-addresses', [
            'name' => 'test',
            'default' => false,
            'address' => [
                'name' => $name,
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::CLIENT_FULL_NAME->value,
            ]);
    }

    /**
     * @dataProvider namesProvider
     */
    public function testCreateBillingAddressesName(string $name): void
    {
        $this->user->givePermissionTo('profile.addresses_manage');

        $this->actingAs($this->user)->postJson('/auth/profile/billing-addresses', [
            'name' => 'test',
            'default' => false,
            'address' => [
                'name' => $name,
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])
            ->assertOk();
    }

    /**
     * @dataProvider invalidNamesProvider
     */
    public function testCreateBillingAddressesShortName(string $name): void
    {
        $this->user->givePermissionTo('profile.addresses_manage');

        $this->actingAs($this->user)->postJson('/auth/profile/billing-addresses', [
            'name' => 'test',
            'default' => false,
            'address' => [
                'name' => $name,
                'phone' => '123456789',
                'address' => 'Testowa 12',
                'zip' => '123',
                'city' => 'testcity',
                'country' => 'ts',
                'vat' => '10',
            ],
        ])
            ->assertOk();
    }
}
