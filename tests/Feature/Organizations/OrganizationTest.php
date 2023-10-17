<?php

namespace Tests\Feature\Organizations;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\ValidationError;
use App\Models\Address;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserRegistered;
use Domain\Organization\Enums\OrganizationStatus;
use Domain\Organization\Models\Organization;
use Domain\Organization\Models\OrganizationToken;
use Domain\Organization\Notifications\OrganizationAccepted;
use Domain\Organization\Notifications\OrganizationInvited;
use Domain\Organization\Notifications\OrganizationRejected;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use WithFaker;

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

    /**
     * @dataProvider authProvider
     */
    public function testRejectOrganization(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.verify');

        $this->organization->update([
            'status' => OrganizationStatus::UNVERIFIED->value,
        ]);

        Notification::fake();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations/id:' . $this->organization->getKey() . '/reject')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->organization->getKey(),
                'status' => OrganizationStatus::REJECTED->value,
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->getKey(),
            'status' => OrganizationStatus::REJECTED->value,
        ]);

        Notification::assertSentTo([$this->organization], OrganizationRejected::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRejectOrganizationAlreadyRejected(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.verify');

        $this->organization->update([
            'status' => OrganizationStatus::REJECTED->value,
        ]);

        Notification::fake();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations/id:' . $this->organization->getKey() . '/reject')
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_ORGANIZATION_SAME_STATUS->name,
                'message' => Exceptions::CLIENT_ORGANIZATION_SAME_STATUS->value,
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->getKey(),
            'status' => OrganizationStatus::REJECTED->value,
        ]);

        Notification::assertNotSentTo([$this->organization], OrganizationRejected::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRejectVerifiedOrganization(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.verify');

        $this->organization->update([
            'status' => OrganizationStatus::VERIFIED->value,
        ]);

        Notification::fake();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations/id:' . $this->organization->getKey() . '/reject')
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_ORGANIZATION_VERIFIED->name,
                'message' => Exceptions::CLIENT_ORGANIZATION_VERIFIED->value,
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->getKey(),
            'status' => OrganizationStatus::VERIFIED->value,
        ]);

        Notification::assertNotSentTo([$this->organization], OrganizationRejected::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAcceptOrganization(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.verify');

        $this->organization->update([
            'status' => OrganizationStatus::UNVERIFIED->value,
        ]);

        Notification::fake();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations/id:' . $this->organization->getKey() . '/accept', [
                'redirect_url' => 'http://localhost',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->organization->getKey(),
                'status' => OrganizationStatus::VERIFIED->value,
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->getKey(),
            'status' => OrganizationStatus::VERIFIED->value,
        ]);

        Notification::assertSentTo([$this->organization], OrganizationAccepted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAcceptOrganizationAlreadyAccepted(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.verify');

        $this->organization->update([
            'status' => OrganizationStatus::VERIFIED->value,
        ]);

        Notification::fake();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations/id:' . $this->organization->getKey() . '/accept', [
                'redirect_url' => 'http://localhost',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_ORGANIZATION_SAME_STATUS->name,
                'message' => Exceptions::CLIENT_ORGANIZATION_SAME_STATUS->value,
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->getKey(),
            'status' => OrganizationStatus::VERIFIED->value,
        ]);

        Notification::assertNotSentTo([$this->organization], OrganizationAccepted::class);
    }

    public function testRegisterInvalidOrganizationToken(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $token = Str::random(128);

        $this->organization->tokens()->save(new OrganizationToken([
            'token' => $token,
            'email' => $email,
            'expires_at' => now()->addHour(),
        ]));

        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'organization_token' => 'invalid_token',
        ])->assertNotFound();
    }

    public function testRegisterExpiredOrganizationToken(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $token = Str::random(128);

        $this->organization->tokens()->save(new OrganizationToken([
            'token' => $token,
            'email' => $email,
            'expires_at' => now()->subMinute(),
        ]));

        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'organization_token' => $token,
        ])->assertUnprocessable();
    }

    public function testRegisterWithOrganizationToken(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $token = Str::random(128);

        $this->organization->tokens()->save(new OrganizationToken([
            'token' => $token,
            'email' => $email,
            'expires_at' => now()->addHour(),
        ]));

        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'organization_token' => $token,
        ])->assertCreated();

        /** @var User $user */
        $user = User::query()->where('email', '=', $email)->first();

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseMissing('organization_tokens', [
            'organization_id' => $this->organization->getKey(),
            'email' => $email,
            'token' => $token,
        ]);

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
        );
    }

    public function testRegisterWithOrganizationTokenInvalidEmail(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $token = Str::random(128);

        $this->organization->tokens()->save(new OrganizationToken([
            'token' => $token,
            'email' => $email,
            'expires_at' => now()->addHour(),
        ]));

        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => 'invalid_email@test.com',
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'organization_token' => $token,
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::ORGANIZATIONTOKENEMAIL->value,
                'message' => Exceptions::CLIENT_ORGANIZATION_TOKEN_EMAIL->value,
            ]);
    }

    public function testInviteToOrganizationUnauthorized(): void
    {
        $this->json('POST', '/organizations/id:' . $this->organization->getKey() . '/invite', [
            'redirect_url' => 'https://localhost/invite',
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testInviteToOrganization(string $user): void
    {
        $this->{$user}->givePermissionTo('organizations.invite');

        $emails = [
            $this->faker->email(),
            $this->faker->email(),
        ];

        Notification::fake();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/organizations/id:' . $this->organization->getKey() . '/invite', [
                'redirect_url' => 'https://localhost/invite',
                'emails' => $emails,
            ])
            ->assertNoContent();

        foreach ($emails as $email) {
            $token = OrganizationToken::query()->where('email', '=', $email)->first();
            Notification::assertSentTo([$token], OrganizationInvited::class);
        }
    }
}
