<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Enums\RoleType;
use App\Enums\ValidationError;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Consent;
use App\Models\Metadata;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class UserTest extends TestCase
{
    public array $expected;
    private string $validPassword = 'V@l1dPa55word';
    private Role $owner;
    private Role $authenticated;
    private Role $unauthenticated;
    private Collection $authenticatedPermissions;

    public function setUp(): void
    {
        parent::setUp();

        User::query()->where('email', 'admin@example.com')->delete();

        /** @var Metadata $metadata */
        $metadata = $this->user->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $metadataPersonal = $this->user->metadataPersonal()->create([
            'name' => 'Personal_metadata',
            'value' => 'personal metadata test',
            'value_type' => MetadataType::STRING,
        ]);

        $this->user->preferences()->associate(UserPreference::create());

        $this->user->save();

        $this->expected = [
            'id' => $this->user->getKey(),
            'email' => $this->user->email,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
            'roles' => [],
            'is_tfa_active' => $this->user->is_tfa_active,
            'metadata' => [
                $metadata->name => $metadata->value,
            ],
            'metadata_personal' => [
                $metadataPersonal->name => $metadataPersonal->value,
            ],
            'created_at' => $this->user->created_at,
        ];

        // Owner role needs to exist for user service to function properly
        $this->owner = Role::updateOrCreate(['name' => 'Owner'])
            ->givePermissionTo(Permission::all());
        $this->owner->type = RoleType::OWNER;
        $this->owner->save();

        $this->authenticated = Role::updateOrCreate(['name' => 'Authenticated']);
        $this->authenticated->type = RoleType::AUTHENTICATED;
        $this->authenticated->save();

        $this->unauthenticated = Role::updateOrCreate(['name' => 'Unathenticated']);
        $this->unauthenticated->type = RoleType::UNAUTHENTICATED;
        $this->unauthenticated->save();

        $this->authenticatedPermissions = $this->authenticated->getAllPermissions()
            ->map(fn ($perm) => $perm->name)
            ->sort()
            ->values();
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/users');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('users.show');

        /** @var User $otherUser */
        $otherUser = User::factory()->create();
        $otherUser->created_at = Carbon::now()->addHour();
        $otherUser->save();

        $response = $this->actingAs($this->$user)->getJson('/users');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                $this->expected,
                [
                    'id' => $otherUser->getKey(),
                    'email' => $otherUser->email,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                    'roles' => [],
                    'created_at' => $otherUser->created_at,
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFull($user): void
    {
        $this->$user->givePermissionTo('users.show');

        /** @var User $otherUser */
        $otherUser = User::factory()->create();
        $otherUser->created_at = Carbon::now()->addHour();
        $otherUser->save();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/users', ['full' => true])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                $this->expected,
                [
                    'id' => $otherUser->getKey(),
                    'email' => $otherUser->email,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                    'roles' => [],
                    'permissions' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSorted($user): void
    {
        $this->$user->givePermissionTo('users.show');

        /** @var User $otherUser */
        $otherUser = User::factory()->create();
        $otherUser->created_at = Carbon::now()->addHour();
        $otherUser->save();

        $response = $this->actingAs($this->$user)->getJson('/users?sort=created_at:desc');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                [
                    'id' => $otherUser->getKey(),
                    'email' => $otherUser->email,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                    'roles' => [],
                ],
                $this->expected,
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexIdsSearch($user): void
    {
        $this->$user->givePermissionTo('users.show');

        /** @var User $firstUser */
        $firstUser = User::factory()->create();
        $firstUser->created_at = Carbon::now()->addHour();
        $firstUser->save();

        /** @var User $secondUser */
        $secondUser = User::factory()->create();
        $secondUser->created_at = Carbon::now()->addHour();
        $secondUser->save();

        // Dummy user to check if response will return only 2 users created above
        User::factory()->create();

        $response = $this->actingAs($this->$user)
            ->json('GET', '/users', [
                'ids' => [
                    $firstUser->getKey(),
                    $secondUser->getKey(),
                ],
            ]);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexNameSearch($user): void
    {
        $this->$user->givePermissionTo('users.show');

        /** @var User $otherUser */
        $otherUser = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/users?name=' . $otherUser->name)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $otherUser->getKey(),
                'email' => $otherUser->email,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'roles' => [],
                'is_tfa_active' => $otherUser->is_tfa_active,
                'consents' => [],
                'birthday_date' => null,
                'phone' => null,
                'phone_country' => null,
                'phone_number' => null,
                'created_at' => $otherUser->created_at,
                'metadata_personal' => [],
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexEmailSearch($user): void
    {
        $this->$user->givePermissionTo('users.show');

        $otherUser = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $this
            ->actingAs($this->$user)
            ->getJson("/users?email={$otherUser->email}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $otherUser->getKey(),
                'email' => $otherUser->email,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'roles' => [],
                'is_tfa_active' => $otherUser->is_tfa_active,
                'consents' => [],
                'birthday_date' => null,
                'phone' => null,
                'phone_country' => null,
                'phone_number' => null,
                'created_at' => $otherUser->created_at,
                'metadata_personal' => [],
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFullSearchName($user): void
    {
        $this->$user->givePermissionTo('users.show');

        $otherUser = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $response = $this->actingAs($this->$user)->getJson('/users?search=' . $otherUser->name);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $otherUser->getKey(),
                'email' => $otherUser->email,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'roles' => [],
                'is_tfa_active' => $otherUser->is_tfa_active,
                'consents' => [],
                'birthday_date' => null,
                'phone' => null,
                'phone_country' => null,
                'phone_number' => null,
                'created_at' => $otherUser->created_at,
                'metadata_personal' => [],
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFullSearchEmail($user): void
    {
        $this->$user->givePermissionTo('users.show');

        $otherUser = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $response = $this->actingAs($this->$user)->getJson('/users?search=' . $otherUser->email);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $otherUser->getKey(),
                'email' => $otherUser->email,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'roles' => [],
                'is_tfa_active' => $otherUser->is_tfa_active,
                'consents' => [],
                'birthday_date' => null,
                'phone' => null,
                'phone_country' => null,
                'phone_number' => null,
                'created_at' => $otherUser->created_at,
                'metadata_personal' => [],
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexConsentNameSearch($user): void
    {
        $this->$user->givePermissionTo('users.show');

        /** @var User $otherUser */
        $otherUser = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $consent = Consent::factory()->create(['required' => false]);

        $otherUser->consents()->save($consent, ['value' => true]);

        $this
            ->actingAs($this->$user)
            ->getJson('/users?consent_name=' . $consent->name)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $otherUser->getKey(),
                'email' => $otherUser->email,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'roles' => [],
                'is_tfa_active' => $otherUser->is_tfa_active,
                'consents' => [
                    [
                        'id' => $consent->getKey(),
                        'name' => $consent->name,
                        'description_html' => $consent->description_html,
                        'required' => $consent->required,
                        'value' => true,
                    ],
                ],
                'birthday_date' => null,
                'phone' => null,
                'phone_country' => null,
                'phone_number' => null,
                'created_at' => $otherUser->created_at,
                'metadata_personal' => [],
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexConsentIdSearch($user): void
    {
        $this->$user->givePermissionTo('users.show');

        /** @var User $otherUser */
        $otherUser = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $consent = Consent::factory()->create(['required' => false]);

        $otherUser->consents()->save($consent, ['value' => true]);

        $this
            ->actingAs($this->$user)
            ->getJson('/users?consent_id=' . $consent->getKey())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $otherUser->getKey(),
                'email' => $otherUser->email,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'roles' => [],
                'is_tfa_active' => $otherUser->is_tfa_active,
                'consents' => [
                    [
                        'id' => $consent->getKey(),
                        'name' => $consent->name,
                        'description_html' => $consent->description_html,
                        'required' => $consent->required,
                        'value' => true,
                    ],
                ],
                'birthday_date' => null,
                'phone' => null,
                'phone_country' => null,
                'phone_number' => null,
                'created_at' => $otherUser->created_at,
                'metadata_personal' => [],
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexRolesSearchArray($user): void
    {
        $this->$user->givePermissionTo('users.show');

        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        /** @var User $firstUser */
        $firstUser = User::factory()->create();
        $firstUser->assignRole($role1);

        /** @var User $secondUser */
        $secondUser = User::factory()->create();
        $secondUser->assignRole($role2);

        User::factory()->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/users', ['roles' => [$role1->getKey(), $role2->getKey()]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $firstUser->getKey()])
            ->assertJsonFragment(['id' => $secondUser->getKey()]);
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->getJson('/users/id:' . $this->user->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('users.show_details');

        $response = $this->actingAs($this->$user)->getJson('/users/id:' . $this->user->getKey());
        $response
            ->assertOk()
            ->assertJson([
                'data' => $this->expected + [
                    'permissions' => [],
                    'preferences' => [
                        'successful_login_attempt_alert' => false,
                        'failed_login_attempt_alert' => true,
                        'new_localization_login_alert' => true,
                        'recovery_code_changed_alert' => true,
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongId($user): void
    {
        $this->$user->givePermissionTo('users.show_details');

        $this
            ->actingAs($this->$user)
            ->getJson('/users/id:its-not-uuid')
            ->assertNotFound();

        $this
            ->actingAs($this->$user)
            ->getJson('/users/id:' . $this->user->getKey() . $this->user->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateMetadata($user): void
    {
        $this->$user->givePermissionTo(['users.show_details', 'users.show_metadata_private']);

        $privateMetadata = $this->user->metadataPrivate()->create([
            'name' => 'hiddenMetadata',
            'value' => 'hidden metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $response = $this->actingAs($this->$user)->getJson('/users/id:' . $this->user->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected +
                [
                    'permissions' => [],
                    'metadata_private' => [$privateMetadata->name => $privateMetadata->value],
                ],
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        Event::fake([UserCreated::class]);

        $response = $this->getJson('/users');
        $response->assertForbidden();

        Event::assertNotDispatched(UserCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
        ];

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/users', $data);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonMissing(['name' => 'Authenticated'])
            ->assertJsonFragment([
                'successful_login_attempt_alert' => false,
                'failed_login_attempt_alert' => true,
                'new_localization_login_alert' => true,
                'recovery_code_changed_alert' => true,
            ]);

        $userId = $response->getData()->data->id;

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user = User::find($userId);
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $this->assertTrue($user->hasAllRoles([$this->authenticated]));

        $this->assertDatabaseHas('user_preferences', [
            'id' => $user->preferences_id,
            'successful_login_attempt_alert' => false,
            'failed_login_attempt_alert' => true,
            'new_localization_login_alert' => true,
            'recovery_code_changed_alert' => true,
        ]);

        Event::assertDispatched(UserCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->postJson('/users', $data)
            ->assertCreated()
            ->assertJsonFragment([
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadataPrivate($user): void
    {
        $this->$user->givePermissionTo(['users.add', 'users.show_metadata_private']);

        Event::fake([UserCreated::class]);

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->postJson('/users', $data)
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadataPersonal($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
            'metadata_personal' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->postJson('/users', $data)
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_personal' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHook($user): void
    {
        $this->$user->givePermissionTo('users.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'UserCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
        ];

        $response = $this->actingAs($this->$user)->postJson('/users', $data);
        $response
            ->assertCreated()
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name']);

        $userId = $response->getData()->data->id;

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $foundUser = User::find($userId);
        $this->assertTrue(Hash::check($data['password'], $foundUser->password));

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof UserCreated;
        });

        $event = new UserCreated($foundUser);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $foundUser) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $foundUser->getKey()
                && $payload['data_type'] === 'User'
                && $payload['event'] === 'UserCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateEmailTaken($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $data = [
            'name' => User::factory()->raw()['name'],
            'email' => $this->$user->email,
            'password' => $this->validPassword,
        ];

        $response = $this->actingAs($this->$user)->postJson('/users', $data);
        $response->assertStatus(422);

        Event::assertNotDispatched(UserCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateEmailTakenByDeletedUser($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $otherUser = User::factory()->create();
        $otherUser->delete();

        $name = User::factory()->raw()['name'];
        $data = [
            'name' => $name,
            'email' => $otherUser->email,
            'password' => $this->validPassword,
        ];

        $response = $this->actingAs($this->$user)->postJson('/users', $data);
        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $otherUser->email,
        ]);

        Event::assertDispatched(UserCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateRolesMissingPermissions($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->$user->givePermissionTo($permission2);

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
            'roles' => [
                $role1->getKey(),
                $role2->getKey(),
                $role3->getKey(),
            ],
        ];

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains(
                    $message,
                    'ClientException(code: 422): '
                    . "Can't give a role with permissions you don't have to the user at"
                );
            });

        $response = $this->actingAs($this->$user)->postJson('/users', $data);
        $response->assertStatus(422);

        $this->assertDatabaseMissing('users', [
            'email' => $data['email'],
        ]);

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateRoles($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        /** @var Role $role1 */
        $role1 = Role::create(['name' => 'Role 1']);
        /** @var Role $role2 */
        $role2 = Role::create(['name' => 'Role 2']);
        /** @var Role $role3 */
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->$user->givePermissionTo([$permission1, $permission2]);

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
            'roles' => [
                $role1->getKey(),
                $role2->getKey(),
                $role3->getKey(),
            ],
        ];

        $permissions = $this->authenticatedPermissions
            ->merge(['permission.1', 'permission.2'])
            ->sort()
            ->values()
            ->toArray();

        $response = $this->actingAs($this->$user)->postJson('/users', $data);
        $response
            ->assertCreated()
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonFragment([[
                'id' => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'is_registration_role' => false,
                'assignable' => true,
                'deletable' => true,
                'users_count' => null,
                'metadata' => [],
            ],
            ])->assertJsonFragment([[
                'id' => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'is_registration_role' => false,
                'assignable' => true,
                'deletable' => true,
                'users_count' => null,
                'metadata' => [],
            ],
            ])->assertJsonFragment([[
                'id' => $role3->getKey(),
                'name' => $role3->name,
                'description' => $role3->description,
                'is_registration_role' => false,
                'assignable' => true,
                'deletable' => true,
                'users_count' => null,
                'metadata' => [],
            ],
            ])->assertJsonPath('data.permissions', $permissions);

        /** @var User $user */
        $user = User::query()
            ->findOrFail($response->getData()->data->id);

        $this->assertTrue(
            $user->hasAllRoles([$role1, $role2, $role3, $this->authenticated]),
        );

        $this->assertTrue(
            $user->hasAllPermissions([$permission1, $permission2]),
        );

        $this->assertTrue(
            Hash::check($data['password'], $user->password),
        );

        Event::assertDispatched(UserCreated::class);
    }

    public static function unassignableProvider(): array
    {
        return [
            'as user Authenticated' => ['user', RoleType::AUTHENTICATED],
            'as user Unauthenticated' => ['user', RoleType::UNAUTHENTICATED],
            'as app Authenticated' => ['application', RoleType::AUTHENTICATED],
            'as app Unauthenticated' => ['application', RoleType::UNAUTHENTICATED],
        ];
    }

    /**
     * @dataProvider unassignableProvider
     */
    public function testCreateUnassignableRole($user, $role): void
    {
        $this->$user->givePermissionTo('users.add');

        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
            'roles' => [
                match ($role) {
                    RoleType::AUTHENTICATED => $this->authenticated->getKey(),
                    RoleType::UNAUTHENTICATED => $this->unauthenticated->getKey(),
                },
            ],
        ];

        $this->actingAs($this->$user)->postJson('/users', $data)->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithPhone($user): void
    {
        $this->$user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $data = User::factory()->raw()
            + [
                'password' => $this->validPassword,
                'birthday_date' => '1990-01-01',
                'phone' => '+48123456789',
            ];

        $this
            ->actingAs($this->$user)
            ->postJson('/users', $data)
            ->assertCreated()
            ->assertJsonFragment([
                'birthday_date' => '1990-01-01',
                'phone' => '+48123456789',
                'phone_country' => 'PL',
                'phone_number' => '12 345 67 89',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
            'birthday_date' => '1990-01-01',
            'phone_country' => 'PL',
            'phone_number' => '12 345 67 89',
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        Event::fake([UserUpdated::class]);

        $response = $this->patchJson('/users/id:' . $this->user->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $otherUser = User::factory()->create();
        $data = User::factory()->raw();

        $response = $this->actingAs($this->$user)->patchJson(
            '/users/id:' . $otherUser->getKey(),
            $data + [
                'birthday_date' => '1990-01-01',
                'phone' => '+48123456789',
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $otherUser->getKey())
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonPath('data.birthday_date', '1990-01-01')
            ->assertJsonPath('data.phone', '+48123456789')
            ->assertJsonPath('data.phone_country', 'PL')
            ->assertJsonPath('data.phone_number', '12 345 67 89');

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->getKey(),
            'name' => $data['name'],
            'email' => $data['email'],
            'birthday_date' => '1990-01-01',
            'phone_country' => 'PL',
            'phone_number' => '12 345 67 89',
        ]);

        Event::assertDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateInvalidPhone($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->$user)->patchJson(
            '/users/id:' . $otherUser->getKey(),
            [
                'phone' => '123456789',
            ],
        );

        $response
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::PHONE,
            ]);

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHook($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'UserUpdated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $otherUser = User::factory()->create();
        $data = User::factory()->raw();

        $response = $this->actingAs($this->$user)->patchJson(
            '/users/id:' . $otherUser->getKey(),
            $data,
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $otherUser->getKey())
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name']);

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->getKey(),
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof UserUpdated;
        });

        $foundUser = User::find($otherUser->getKey());

        $event = new UserUpdated($foundUser);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $foundUser) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $foundUser->getKey()
                && $payload['data_type'] === 'User'
                && $payload['event'] === 'UserUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAddRolesMissingPermissions($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $otherUser = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->$user->givePermissionTo([$permission2]);

        $data = [
            'roles' => [
                $role1->getKey(),
                $role2->getKey(),
                $role3->getKey(),
            ],
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/users/id:' . $otherUser->getKey(),
            $data,
        );
        $response->assertStatus(422);
        $otherUser->refresh();

        $this->assertFalse(
            $otherUser->hasAnyRole([$role1, $role2, $role3]),
        );

        $this->assertFalse(
            $otherUser->hasAnyPermission([$permission1, $permission2]),
        );

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAddRoles($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        /** @var Role $role1 */
        $role1 = Role::create(['name' => 'Role 1']);
        /** @var Role $role2 */
        $role2 = Role::create(['name' => 'Role 2']);
        /** @var Role $role3 */
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->$user->givePermissionTo([$permission1, $permission2]);

        $data = [
            'roles' => [
                $role1->getKey(),
                $role2->getKey(),
                $role3->getKey(),
            ],
        ];

        $permissions = $this->authenticatedPermissions
            ->merge(['permission.1', 'permission.2'])
            ->sort()
            ->values()
            ->toArray();

        $response = $this->actingAs($this->$user)->patchJson(
            '/users/id:' . $otherUser->getKey(),
            $data,
        );
        $response
            ->assertOk()
            ->assertJsonFragment([[
                'id' => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'is_registration_role' => false,
                'assignable' => true,
                'deletable' => true,
                'users_count' => null,
                'metadata' => [],
            ],
            ])->assertJsonFragment([[
                'id' => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'is_registration_role' => false,
                'assignable' => true,
                'deletable' => true,
                'users_count' => null,
                'metadata' => [],
            ],
            ])->assertJsonFragment([[
                'id' => $role3->getKey(),
                'name' => $role3->name,
                'description' => $role3->description,
                'is_registration_role' => false,
                'assignable' => true,
                'deletable' => true,
                'users_count' => null,
                'metadata' => [],
            ],
            ])->assertJsonPath('data.permissions', $permissions);

        $otherUser->refresh();

        $this->assertTrue(
            $otherUser->hasAllRoles([$role1, $role2, $role3, $this->authenticated]),
        );

        $this->assertTrue(
            $otherUser->hasAllPermissions([$permission1, $permission2]),
        );

        Event::assertDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider unassignableProvider
     */
    public function testUpdateUnassignableRole($user, $role): void
    {
        $this->$user->givePermissionTo('users.edit');

        $otherUser = User::factory()->create();

        $data = [
            'roles' => [
                match ($role) {
                    RoleType::AUTHENTICATED => $this->authenticated->getKey(),
                    RoleType::UNAUTHENTICATED => $this->unauthenticated->getKey(),
                },
            ],
        ];

        $this->actingAs($this->$user)->patchJson('/users/id:' . $otherUser->getKey(), $data)
            ->assertStatus(422);
    }

    public function testUpdateRemoveRolesMissingPermissions(): void
    {
        $this->user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $otherUser = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);
        $otherUser->assignRole([$role1, $role2, $role3]);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->user->givePermissionTo([$permission2]);

        $data = [
            'roles' => [],
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $otherUser->getKey(),
            $data,
        );
        $response->assertStatus(422);
        $otherUser->refresh();

        $this->assertTrue(
            $otherUser->hasAllRoles([$role1, $role2, $role3]),
        );

        $this->assertTrue(
            $otherUser->hasAllPermissions([$permission1, $permission2]),
        );

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateRemoveRoles($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $otherUser = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);
        $otherUser->assignRole([$role1, $role2, $role3]);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->$user->givePermissionTo([$permission1, $permission2]);

        $data = [
            'roles' => [],
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/users/id:' . $otherUser->getKey(),
            $data,
        );
        $response
            ->assertOk()
            ->assertJsonPath('data.permissions', $this->authenticatedPermissions->toArray());
        $otherUser->refresh();

        $this->assertTrue($otherUser->hasAllRoles([$this->authenticated]));

        $this->assertFalse(
            $otherUser->hasAnyRole([$role1, $role2, $role3]),
        );

        $this->assertFalse(
            $otherUser->hasAnyPermission([$permission1, $permission2]),
        );

        Event::assertDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSameEmail($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/users/id:' . $this->user->getKey(), [
            'email' => $this->user->email,
        ]);
        $response
            ->assertOk()
            ->assertJsonPath('data.id', $this->user->getKey())
            ->assertJsonPath('data.email', $this->user->email)
            ->assertJsonPath('data.name', $this->user->name);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);

        Event::assertDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSameName($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/users/id:' . $this->user->getKey(), [
            'name' => $this->user->name,
        ]);
        $response
            ->assertOk()
            ->assertJsonPath('data.id', $this->user->getKey())
            ->assertJsonPath('data.email', $this->user->email)
            ->assertJsonPath('data.name', $this->user->name);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);

        Event::assertDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateEmailTaken($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $other = User::factory()->create();

        $response = $this->actingAs($this->$user)->patchJson('/users/id:' . $this->user->getKey(), [
            'email' => $other->email,
        ]);
        $response->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'email' => $this->user->email,
        ]);

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateEmailTakenByDeletedUser($user): void
    {
        $this->$user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $other = User::factory()->create();
        $other->delete();

        $response = $this->actingAs($this->$user)->patchJson('/users/id:' . $this->user->getKey(), [
            'email' => $other->email,
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'email' => $other->email,
        ]);

        Event::assertDispatched(UserUpdated::class);
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
        $this->$user->givePermissionTo('users.remove');

        Event::fake([UserDeleted::class]);

        $response = $this->actingAs($this->$user)->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->user);

        Event::assertDispatched(UserDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHook($user): void
    {
        $this->$user->givePermissionTo('users.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'UserDeleted',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)->deleteJson('/users/id:' . $this->user->getKey());
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
        $this->$user->givePermissionTo('users.remove');

        Event::fake([UserDeleted::class]);

        $owner = User::factory()->create();
        $owner->assignRole($this->owner);

        $response = $this->actingAs($this->$user)->deleteJson('/users/id:' . $owner->getKey());
        $response->assertStatus(422);

        Event::assertNotDispatched(UserDeleted::class);
    }

    public function testDeleteOnlyOwner(): void
    {
        Event::fake([UserDeleted::class]);

        $this->user->givePermissionTo('users.remove');
        $this->user->assignRole($this->owner);

        $response = $this->actingAs($this->user)->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertStatus(422);

        Event::assertNotDispatched(UserDeleted::class);
    }

    public function testDeleteOwner(): void
    {
        $this->user->givePermissionTo('users.remove');

        Event::fake([UserDeleted::class]);

        $owner = User::factory()->create();
        $owner->assignRole($this->owner);
        $this->user->assignRole($this->owner);

        $response = $this->actingAs($this->user)->deleteJson('/users/id:' . $owner->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($owner);

        Event::assertDispatched(UserDeleted::class);
    }
}
