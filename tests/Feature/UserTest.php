<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WebHook;
use Carbon\Carbon;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\WebhookServer\CallWebhookJob;
use Illuminate\Support\Facades\Log;
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

        $this->expected = [
            'id' => $this->user->getKey(),
            'email' => $this->user->email,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
            'roles' => [],
            'is_tfa_active' => $this->user->is_tfa_active,
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
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSorted($user): void
    {
        $this->$user->givePermissionTo('users.show');

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
            ]]);
    }

    public function testIndexNameSearch(): void
    {
        $this->user->givePermissionTo('users.show');

        $user = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $this
            ->actingAs($this->user)
            ->getJson('/users?name=' . $user->name)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'roles' => [],
                'is_tfa_active' => $user->is_tfa_active,
            ]);
    }

    public function testIndexEmailSearch(): void
    {
        $this->user->givePermissionTo('users.show');

        $user = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $this
            ->actingAs($this->user)
            ->getJson('/users?email=' . $user->email)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'roles' => [],
                'is_tfa_active' => $user->is_tfa_active,
            ]);
    }

    public function testIndexFullSearchName(): void
    {
        $this->user->givePermissionTo('users.show');

        $user = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $response = $this->actingAs($this->user)->getJson('/users?search=' . $user->name);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'roles' => [],
                'is_tfa_active' => $user->is_tfa_active,
            ]);
    }

    public function testIndexFullSearchEmail(): void
    {
        $this->user->givePermissionTo('users.show');

        $user = User::factory([
            'is_tfa_active' => false,
        ])->create();

        $response = $this->actingAs($this->user)->getJson('/users?search=' . $user->email);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'roles' => [],
                'is_tfa_active' => $user->is_tfa_active,
            ]);
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
            ->assertJson(['data' => $this->expected + ['permissions' => []]]);
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

        $response = $this->actingAs($this->$user)->postJson('/users', $data);
        $response
            ->assertCreated()
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonFragment(['name' => 'Authenticated']);

        $userId = $response->getData()->data->id;

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user = User::find($userId);
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $this->assertTrue($user->hasAllRoles([$this->authenticated]));

        Event::assertDispatched(UserCreated::class);
    }

    public function testCreateWithWebHook(): void
    {
        $this->user->givePermissionTo('users.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'UserCreated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $data = User::factory()->raw() + [
                'password' => $this->validPassword,
            ];

        $response = $this->actingAs($this->user)->postJson('/users', $data);
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

        $user = User::find($userId);
        $this->assertTrue(Hash::check($data['password'], $user->password));

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof UserCreated;
        });

        $event = new UserCreated($user);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $user->getKey()
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
    public function testCreateEmailTakenByDeletedUser(): void
    {
        $this->user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $user = User::factory()->create();
        $user->delete();

        $name = User::factory()->raw()['name'];
        $data = [
            'name' => $name,
            'email' => $user->email,
            'password' => $this->validPassword,
        ];

        $response = $this->actingAs($this->user)->postJson('/users', $data);
        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $user->email,
        ]);

        Event::assertDispatched(UserCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateRolesMissingPermissions(): void
    {
        $this->user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->user->givePermissionTo($permission2);

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
                    "AuthException(code: 0): "
                    . "Can't give a role with permissions you don't have to the user at"
                );
            });

        $response = $this->actingAs($this->user)->postJson('/users', $data);
        $response->assertStatus(422);

        $this->assertDatabaseMissing('users', [
            'email' => $data['email'],
        ]);

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateRoles(): void
    {
        $this->user->givePermissionTo('users.add');

        Event::fake([UserCreated::class]);

        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->user->givePermissionTo([$permission1, $permission2]);

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

        $response = $this->actingAs($this->user)->postJson('/users', $data);
        $response
            ->assertCreated()
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonFragment([[
                'id' => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'assignable' => true,
                'deletable' => true,
            ]])->assertJsonFragment([[
                'id' => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'assignable' => true,
                'deletable' => true,
            ]])->assertJsonFragment([[
                'id' => $role3->getKey(),
                'name' => $role3->name,
                'description' => $role3->description,
                'assignable' => true,
                'deletable' => true,
            ]])->assertJsonFragment([[
                'id' => $this->authenticated->getKey(),
                'name' => $this->authenticated->name,
                'description' => $this->authenticated->description,
                'assignable' => false,
                'deletable' => false,
            ]])->assertJsonPath('data.permissions', $permissions);

        $user = User::findOrFail($response->getData()->data->id);

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

    public function unassignableProvider(): array
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
                    match($role) {
                        RoleType::AUTHENTICATED => $this->authenticated->getKey(),
                        RoleType::UNAUTHENTICATED => $this->unauthenticated->getKey(),
                    }
                ],
            ];

        $this->actingAs($this->$user)->postJson('/users', $data)->assertStatus(422);
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
    public function testUpdate(): void
    {
        $this->user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $user = User::factory()->create();
        $data = User::factory()->raw();

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $user->getKey(),
            $data,
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->getKey())
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name']);

        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        Event::assertDispatched(UserUpdated::class);
    }

    public function testUpdateWithWebHook(): void
    {
        $this->user->givePermissionTo('users.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'UserUpdated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $user = User::factory()->create();
        $data = User::factory()->raw();

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $user->getKey(),
            $data,
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->getKey())
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name']);

        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof UserUpdated;
        });

        $user = User::find($user->getKey());

        $event = new UserUpdated($user);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $user->getKey()
                && $payload['data_type'] === 'User'
                && $payload['event'] === 'UserUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAddRolesMissingPermissions(): void
    {
        $this->user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->user->givePermissionTo([$permission2]);

        $data = [
            'roles' => [
                $role1->getKey(),
                $role2->getKey(),
                $role3->getKey(),
            ],
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $user->getKey(),
            $data,
        );
        $response->assertStatus(422);
        $user->refresh();

        $this->assertFalse(
            $user->hasAnyRole([$role1, $role2, $role3]),
        );

        $this->assertFalse(
            $user->hasAnyPermission([$permission1, $permission2]),
        );

        Event::assertNotDispatched(UserUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAddRoles(): void
    {
        $this->user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->user->givePermissionTo([$permission1, $permission2]);

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

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $user->getKey(),
            $data,
        );
        $response
            ->assertOk()
            ->assertJsonFragment([[
                'id' => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'assignable' => true,
                'deletable' => true,
            ]])->assertJsonFragment([[
                'id' => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'assignable' => true,
                'deletable' => true,
            ]])->assertJsonFragment([[
                'id' => $role3->getKey(),
                'name' => $role3->name,
                'description' => $role3->description,
                'assignable' => true,
                'deletable' => true,
            ]])->assertJsonFragment([[
                'id' => $this->authenticated->getKey(),
                'name' => $this->authenticated->name,
                'description' => $this->authenticated->description,
                'assignable' => false,
                'deletable' => false,
            ]])->assertJsonPath('data.permissions', $permissions);

        $user->refresh();

        $this->assertTrue(
            $user->hasAllRoles([$role1, $role2, $role3, $this->authenticated]),
        );

        $this->assertTrue(
            $user->hasAllPermissions([$permission1, $permission2]),
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
                match($role) {
                    RoleType::AUTHENTICATED => $this->authenticated->getKey(),
                    RoleType::UNAUTHENTICATED => $this->unauthenticated->getKey(),
                }
            ],
        ];

        $this->actingAs($this->$user)->patchJson('/users/id:' . $otherUser->getKey(), $data)
            ->assertStatus(422);
    }

    public function testUpdateRemoveRolesMissingPermissions(): void
    {
        $this->user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);
        $user->assignRole([$role1, $role2, $role3]);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->user->givePermissionTo([$permission2]);

        $data = [
            'roles' => [],
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $user->getKey(),
            $data,
        );
        $response->assertStatus(422);
        $user->refresh();

        $this->assertTrue(
            $user->hasAllRoles([$role1, $role2, $role3]),
        );

        $this->assertTrue(
            $user->hasAllPermissions([$permission1, $permission2]),
        );

        Event::assertNotDispatched(UserUpdated::class);
    }

    public function testUpdateRemoveRoles(): void
    {
        $this->user->givePermissionTo('users.edit');

        Event::fake([UserUpdated::class]);

        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        $role3 = Role::create(['name' => 'Role 3']);
        $user->assignRole([$role1, $role2, $role3]);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $role2->syncPermissions([$permission1]);
        $this->user->givePermissionTo([$permission1, $permission2]);

        $data = [
            'roles' => [],
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $user->getKey(),
            $data,
        );
        $response
            ->assertOk()
            ->assertJsonPath('data.roles', [
                0 => [
                    'id' => $this->authenticated->getKey(),
                    'name' => $this->authenticated->name,
                    'description' => $this->authenticated->description,
                    'assignable' => false,
                    'deletable' => false,
                ]
            ])
            ->assertJsonPath('data.permissions', $this->authenticatedPermissions->toArray());
        $user->refresh();

        $this->assertTrue($user->hasAllRoles([$this->authenticated]));

        $this->assertFalse(
            $user->hasAnyRole([$role1, $role2, $role3]),
        );

        $this->assertFalse(
            $user->hasAnyPermission([$permission1, $permission2]),
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

    public function testDeleteWithWebHook(): void
    {
        $this->user->givePermissionTo('users.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'UserDeleted'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->user)->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->user);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof UserDeleted;
        });

        $user = $this->user;

        $event = new UserDeleted($user);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $user->getKey()
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
