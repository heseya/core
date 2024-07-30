<?php

namespace Tests\Feature\Organizations;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\ValidationError;
use App\Events\OrganizationCreated;
use App\Events\OrganizationDeleted;
use App\Events\OrganizationUpdated;
use App\Events\UserCreated;
use App\Listeners\WebHookEventListener;
use App\Mail\OrganizationRegistered;
use App\Models\Address;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\WebHook;
use Domain\Consent\Enums\ConsentType;
use Domain\Consent\Models\Consent;
use Domain\Organization\Models\Organization;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Spatie\WebhookServer\CallWebhookJob;
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

    /**
     * @dataProvider authProvider
     */
    public function testOrganizationUsers(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.show');

        $userIn = User::factory()->create();
        $anotherUser = User::factory()->create();

        $this->organization->users()->attach($userIn->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/organizations/id:' . $this->organization->getKey() . '/users')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $userIn->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $anotherUser->getKey(),
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

        Event::fake(OrganizationCreated::class);

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
                'consents' => [],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'billing_email' => 'test@test.test',
                'client_id' => 'CLIENT_01',
            ])
            ->assertJsonFragment($address);

        Event::assertDispatched(OrganizationCreated::class);

        $this->assertDatabaseHas('organizations', [
            'billing_email' => 'test@test.test',
            'client_id' => 'CLIENT_01',
            'is_complete' => false,
        ]);

        $this->assertDatabaseHas('addresses', $address);
        $this->assertDatabaseHas('addresses', $shippingAddress);

        /** @var Organization $organization */
        $organization = Organization::query()->where('id', '=', $response->getData()->data->id)->first();

        $this->assertDatabaseHas('organization_saved_addresses', [
            'organization_id' => $organization->getKey(),
            'default' => true,
            'name' => 'Shipping address',
        ]);
    }

    public function testCreateWithWebhook(): void
    {
        $this->user->givePermissionTo('organizations.add');

        $address = Address::factory()->definition();
        $address['vat'] = '987654321';
        $shippingAddress = Address::factory()->definition();

        /** @var WebHook $webHook */
        $webHook = WebHook::factory()->create([
            'events' => [
                'OrganizationCreated',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake(OrganizationCreated::class);

        $response = $this
            ->actingAs($this->user)
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
                'consents' => [],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'billing_email' => 'test@test.test',
                'client_id' => 'CLIENT_01',
            ])
            ->assertJsonFragment($address);

        Event::assertDispatched(OrganizationCreated::class);

        /** @var Organization $organization */
        $organization = Organization::query()->where('id', '=', $response->getData()->data->id)->first();
        $event = new OrganizationCreated($organization);

        Bus::fake();

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $organization) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $organization->getKey()
                && $payload['data_type'] === 'Organization'
                && $payload['event'] === 'OrganizationCreated';
        });
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

        Event::fake(OrganizationUpdated::class);

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

        Event::assertDispatched(OrganizationUpdated::class);
    }

    public function testUpdateWithWebhook(): void
    {
        $this->user->givePermissionTo('organizations.edit');

        Event::fake(OrganizationUpdated::class);

        /** @var WebHook $webHook */
        $webHook = WebHook::factory()->create([
            'events' => [
                'OrganizationUpdated',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $this
            ->actingAs($this->user)
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

        $this->organization->refresh();
        Event::assertDispatched(OrganizationUpdated::class);

        $event = new OrganizationUpdated($this->organization);

        Bus::fake();

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $this->organization->getKey()
                && $payload['data']['billing_email'] === 'new.email@test.com'
                && $payload['data_type'] === 'Organization'
                && $payload['event'] === 'OrganizationUpdated';
        });
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

        Event::fake(OrganizationDeleted::class);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', '/organizations/id:' . $this->organization->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('organizations', [
            'id' => $this->organization->getKey(),
        ]);

        Event::assertDispatched(OrganizationDeleted::class);
    }

    public function testRemoveWithWebhook(): void
    {
        $this->user->givePermissionTo('organizations.remove');

        /** @var WebHook $webHook */
        $webHook = WebHook::factory()->create([
            'events' => [
                'OrganizationDeleted',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake(OrganizationDeleted::class);

        $this
            ->actingAs($this->user)
            ->json('DELETE', '/organizations/id:' . $this->organization->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('organizations', [
            'id' => $this->organization->getKey(),
        ]);

        Event::assertDispatched(OrganizationDeleted::class);

        $event = new OrganizationDeleted($this->organization);

        Bus::fake();

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $this->organization->getKey()
                && $payload['data_type'] === 'Organization'
                && $payload['event'] === 'OrganizationDeleted';
        });
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
                'creator_password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
                'creator_name' => 'Jan Kowalski',
            ])
            ->assertForbidden();
    }

    public function testRegister(): void
    {
        $owner = Role::where('type', RoleType::OWNER)->firstOrFail();

        $admin1 = User::factory()->create();
        $admin1->roles()->attach($owner);
        $admin1->preferences()->associate(
            UserPreference::create([
                'new_organization_alert' => true,
            ]),
        );
        $admin1->save();

        $admin2 = User::factory()->create();
        $admin2->roles()->attach($owner);
        $admin2->preferences()->associate(
            UserPreference::create([
                'new_organization_alert' => false,
            ])
        );
        $admin2->save();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.organization_register');

        $address = Address::factory()->definition();
        $address['vat'] = '321456987';

        Event::fake([OrganizationCreated::class, UserCreated::class]);
        Mail::fake();

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
                'consents' => [],
            ])
            ->assertCreated();

        Event::assertDispatched(OrganizationCreated::class);
        Event::assertDispatched(UserCreated::class);

        Mail::assertSent(OrganizationRegistered::class, 1);

        Mail::assertSent(OrganizationRegistered::class, function (OrganizationRegistered $mail) use ($admin1) {
            $mail->assertTo($admin1->email);

            return true;
        });

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

    public function testRegisterWithConsent(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.organization_register');

        $address = Address::factory()->definition();
        $address['vat'] = '321456987';

        $consent = Consent::factory()->create([
            'required' => true,
            'type' => ConsentType::ORGANIZATION,
        ]);

        Event::fake([OrganizationCreated::class, UserCreated::class]);
        Mail::fake();

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
                'consents' => [
                    $consent->getKey() => true,
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('organizations', [
            'billing_email' => 'test.organization@example.com',
        ]);

        $this->assertDatabaseHas('consent_organization', [
            'consent_id' => $consent->getKey(),
            'organization_id' => $response->getData()->data->id,
            'value' => true,
        ]);
    }

    public function testRegisterWithoutRequiredConsent(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.organization_register');

        $address = Address::factory()->definition();
        $address['vat'] = '321456987';

        Consent::factory()->create([
            'required' => true,
            'type' => ConsentType::ORGANIZATION,
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
                'consents' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::REQUIREDCONSENTS->value,
                'message' => Exceptions::CLIENT_NOT_ACCEPTED_ALL_REQUIRED_CONSENTS->value,
            ]);
    }

    public function testShowMyOrganizationUserWithoutOrganization(): void
    {
        $this->actingAs($this->user)
            ->json('GET', 'my/organization')
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_USER_NOT_IN_ORGANIZATION->name,
                'message' => Exceptions::CLIENT_USER_NOT_IN_ORGANIZATION->value,
            ]);
    }

    public function testShowMyOrganization(): void
    {
        $this->user->organizations()->attach($this->organization->getKey());

        $this->actingAs($this->user)
            ->json('GET', 'my/organization')
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

    public function testEditMyOrganizationUserWithoutOrganization(): void
    {
        $address = Address::factory()->definition();
        $address['vat'] = '123456789';

        $this->actingAs($this->user)
            ->json('PATCH', 'my/organization', [
                'billing_email' => 'new.email@example.com',
                'billing_address' => $address
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::MYORGANIZATIONUNIQUEVAT->value,
                'message' => Exceptions::CLIENT_USER_NOT_IN_ORGANIZATION->value,
            ]);
    }

    public function testEditMyOrganization(): void
    {
        $this->user->organizations()->attach($this->organization->getKey());
        $address = Address::factory()->definition();
        $address['vat'] = '123456789';

        Event::fake(OrganizationUpdated::class);

        $this->actingAs($this->user)
            ->json('PATCH', 'my/organization', [
                'billing_email' => 'new.email@example.com',
                'billing_address' => $address
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->organization->getKey(),
                'billing_email' => 'new.email@example.com',
            ])
            ->assertJsonFragment($address);

        Event::assertDispatched(OrganizationUpdated::class);
    }

    public function testEditMyOrganizationRequiredConsent(): void
    {
        $this->user->organizations()->attach($this->organization->getKey());
        $address = Address::factory()->definition();
        $address['vat'] = '123456789';

        $consent = Consent::factory()->create([
            'required' => true,
            'type' => ConsentType::ORGANIZATION,
        ]);

        $this->organization->consents()->attach($consent->getKey(), ['value' => true]);

        Event::fake(OrganizationUpdated::class);

        $this->actingAs($this->user)
            ->json('PATCH', 'my/organization', [
                'billing_email' => 'new.email@example.com',
                'billing_address' => $address,
                'consents' => [
                    $consent->getKey() => false,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::REQUIREDCONSENTS->value,
                'message' => Exceptions::CLIENT_NOT_ACCEPTED_ALL_REQUIRED_CONSENTS->value,
            ]);
    }

    public function testEditMyOrganizationExistingVat(): void
    {
        $this->user->organizations()->attach($this->organization->getKey());

        $address = Address::factory()->definition();
        $address['vat'] = '123456789';

        $existingAddress = Address::create($address);
        Organization::factory()->create([
            'billing_address_id' => $existingAddress->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
        ]);

        $this->actingAs($this->user)
            ->json('PATCH', 'my/organization', [
                'billing_email' => 'new.email@example.com',
                'billing_address' => $address
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::MYORGANIZATIONUNIQUEVAT->value,
                'message' => Exceptions::CLIENT_ORGANIZATION_EXIST->value,
            ]);
    }
}
