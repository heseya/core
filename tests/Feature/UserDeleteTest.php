<?php

use App\Enums\AuthProviderKey;
use App\Enums\ConditionType;
use App\Enums\RoleType;
use App\Enums\SavedAddressType;
use App\Events\UserDeleted;
use App\Listeners\WebHookEventListener;
use App\Models\Address;
use App\Models\ConditionGroup;
use App\Models\Consent;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Metadata;
use App\Models\MetadataPersonal;
use App\Models\OneTimeSecurityCode;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Role;
use App\Models\SavedAddress;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\UserProvider;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class UserDeleteTest extends TestCase
{
    private Role $owner;
    private Collection $authenticatedPermissions;

    public function setUp(): void
    {
        parent::setUp();

        User::query()->where('email', 'admin@example.com')->delete();

        $this->user->preferences()->associate(UserPreference::create());
        $this->user->save();

        // Owner role needs to exist for user service to function properly
        $this->owner = Role::updateOrCreate(['name' => 'Owner'])
            ->givePermissionTo(Permission::all());
        $this->owner->type = RoleType::OWNER;
        $this->owner->save();

        $authenticated = Role::updateOrCreate(['name' => 'Authenticated']);
        $authenticated->type = RoleType::AUTHENTICATED;
        $authenticated->save();

        $this->authenticatedPermissions = $authenticated->getAllPermissions()
            ->map(fn ($perm) => $perm->name)
            ->sort()
            ->values();
    }

    public function testDeleteUnauthorized(): void
    {
        Event::fake([UserDeleted::class]);

        $response = $this->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(UserDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        Event::fake([UserDeleted::class]);

        $email = $this->user->email;

        $response = $this->actingAs($this->{$user})->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->user->refresh());
        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'name' => 'Deleted user',
            'password' => null,
            'remember_token' => null,
            'tfa_type' => null,
            'tfa_secret' => null,
            'is_tfa_active' => false,
            'preferences_id' => null,
            'birthday_date' => null,
            'phone_country' => null,
            'phone_number' => null,
        ]);

        $this->assertNotEquals($email, $this->user->email);

        Event::assertDispatched(UserDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithShippingAddress($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $address = Address::factory()->create();
        $savedAddress = SavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'user_id' => $this->user->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
        $this->assertDatabaseMissing('addresses', [
            'id' => $address->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithBillingAddresses($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $address = Address::factory()->create();
        $savedAddress = SavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'user_id' => $this->user->getKey(),
            'type' => SavedAddressType::BILLING,
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
        $this->assertDatabaseMissing('addresses', [
            'id' => $address->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithShippingOrderAddresses($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $address = Address::factory()->create();
        $savedAddress = SavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'user_id' => $this->user->getKey(),
            'type' => SavedAddressType::SHIPPING,
        ]);
        Order::factory()->create([
            'shipping_address_id' => $address->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithBillingOrderAddresses($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $address = Address::factory()->create();
        $savedAddress = SavedAddress::factory()->create([
            'address_id' => $address->getKey(),
            'user_id' => $this->user->getKey(),
            'type' => SavedAddressType::BILLING,
        ]);
        Order::factory()->create([
            'billing_address_id' => $address->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('saved_addresses', [
            'id' => $savedAddress->getKey(),
        ]);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithConsents($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $consent = Consent::factory()->create();
        $this->user->consents()->save($consent, ['value' => true]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('consent_user', [
            'user_id' => $this->user->getKey(),
            'consent_id' => $consent->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithDiscountConditions($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $discount = Discount::factory()->create();
        $conditionGroup = ConditionGroup::query()->create();
        $discountCondition = DiscountCondition::query()->create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [
                    $this->user->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);
        $discountCondition->users()->attach($this->user);
        $discount->conditionGroups()->attach($conditionGroup);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('model_has_discount_conditions', [
            'model_id' => $this->user->getKey(),
            'discount_condition_id' => $discountCondition->getKey(),
        ]);

        $this->assertNotContains(
            $this->user->getKey(),
            $discountCondition->fresh()->value['users'],
        );
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithFavouriteProductSets($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $productSet = ProductSet::factory()->create();
        $this->user->favouriteProductSets()->create([
            'product_set_id' => $productSet->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('favourite_product_sets', [
            'user_id' => $this->user->getKey(),
            'product_set_id' => $productSet->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithMetadata($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $metadata = Metadata::factory()->make([
            'public' => true,
        ]);
        $this->user->metadata()->save($metadata);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('metadata', [
            'model_id' => $this->user->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithMetadataPrivate($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $metadata = Metadata::factory()->make([
            'public' => false,
        ]);
        $this->user->metadataPrivate()->save($metadata);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('metadata', [
            'model_id' => $this->user->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithMetadataPersonal($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $metadata = MetadataPersonal::factory()->make();
        $this->user->metadataPersonal()->save($metadata);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('metadata_personals', [
            'model_id' => $this->user->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithSecurityCodes($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $securityCode = OneTimeSecurityCode::factory()->create([
            'user_id' => $this->user->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertModelMissing($securityCode);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithOrderAssociation($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $order = Order::factory()->create([
            'buyer_id' => $this->user->getKey(),
            'buyer_type' => User::class,
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertModelExists($order->refresh());
        $this->assertNull($order->buyer_id);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithRoles($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $role = Role::factory()->create();
        $this->user->assignRole($role);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $user = $this->user->fresh();
        $this->assertFalse($user->hasRole($role));
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithLoginAttempts($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $loginAttempt = $this->user->loginAttempts()->create([
            'ip' => 'test',
            'user_agent' => 'test',
            'fingerprint' => 'test',
            'logged' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertModelMissing($loginAttempt);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithProviders($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $provider = UserProvider::query()->create([
            'user_id' => $this->user->getKey(),
            'provider' => AuthProviderKey::GITHUB,
            'provider_user_id' => 'test',
            'merge_token' => 'test',
            'merge_token_expires_at' => now()->addDay(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertModelMissing($provider);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWishlistProducts($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $product = Product::factory()->create();
        $wishlistProduct = $this->user->wishlistProducts()->create([
            'product_id' => $product->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertModelMissing($wishlistProduct);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithAudits($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $audit = $this->user->audits()->create([
            'event' => 'created',
            'auditable_id' => $this->user->getKey(),
            'auditable_type' => User::class,
            'old_values' => '',
            'new_values' => '',
            'url' => 'test',
            'ip_address' => 'test',
            'user_agent' => 'test',
            'tags' => '',
            'created_at' => now(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertModelMissing($audit);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithPreferences($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $preferences = $this->user->preferences;

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertNoContent();

        $this->assertModelMissing($preferences);
    }

    public function testSelfDeleteForbidden(): void
    {
        $this
            ->actingAs($this->user)
            ->postJson('/users/self-remove')
            ->assertForbidden();

        $this->assertNotSoftDeleted($this->user->refresh());
    }

    public function testSelfDeleteInvalidPassword(): void
    {
        $this->user->givePermissionTo('users.self_remove');

        $this
            ->actingAs($this->user)
            ->postJson('/users/self-remove', [
                'password' => 'invalid',
            ])
            ->assertUnprocessable();

        $this->assertNotSoftDeleted($this->user->refresh());
    }

    public function testSelfDelete(): void
    {
        $this->user->givePermissionTo('users.self_remove');

        $this
            ->actingAs($this->user)
            ->postJson('/users/self-remove', [
                'password' => $this->password,
            ])
            ->assertNoContent();

        $this->assertSoftDeleted($this->user->refresh());
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHook($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'UserDeleted',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->{$user})->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->user);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof UserDeleted;
        });

        $otherUser = $this->user;

        $event = new UserDeleted($otherUser);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $otherUser) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $otherUser->getKey()
                && $payload['data_type'] === 'User'
                && $payload['event'] === 'UserDeleted';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOwnerUnauthorized($user): void
    {
        $this->{$user}->givePermissionTo('users.remove');

        Event::fake([UserDeleted::class]);

        $owner = User::factory()->create();
        $owner->assignRole($this->owner);

        $response = $this->actingAs($this->{$user})->deleteJson('/users/id:' . $owner->getKey());
        $response->assertStatus(422);

        Event::assertNotDispatched(UserDeleted::class);
    }

    public function testDeleteOnlyOwner(): void
    {
        Event::fake([UserDeleted::class]);

        $this->user->givePermissionTo('users.remove');
        $this->user->assignRole($this->owner);

        $this
            ->actingAs($this->user)
            ->deleteJson('/users/id:' . $this->user->getKey())
            ->assertUnprocessable();

        $this->assertNotSoftDeleted($this->user->refresh());

        Event::assertNotDispatched(UserDeleted::class);
    }

    public function testDeleteOwner(): void
    {
        $this->user->givePermissionTo('users.remove');

        Event::fake([UserDeleted::class]);

        $owner = User::factory()->create();
        $owner->assignRole($this->owner);
        $this->user->assignRole($this->owner);

        $this
            ->actingAs($this->user)
            ->deleteJson('/users/id:' . $owner->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($owner);

        Event::assertDispatched(UserDeleted::class);
    }
}
