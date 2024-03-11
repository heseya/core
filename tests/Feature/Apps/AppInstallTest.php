<?php

namespace Tests\Feature\Apps;

use App\Enums\RoleType;
use App\Models\App;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\UrlServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\App as FacadesApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use TRegx\PhpUnit\DataProviders\DataProvider;

class AppInstallTest extends TestCase
{
    use RefreshDatabase;

    private string $url = 'https://example.com:9000';
    private UrlServiceContract $urlService;
    /** @var array<string, mixed> */
    private array $fakeResponse;
    /** @var array<string, string> */
    private array $appData;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlService = FacadesApp::make(UrlServiceContract::class);
        $this->url = $this->urlService->normalizeUrl($this->url);

        $this->fakeResponse = [
            'name' => 'App name',
            'author' => 'Mr. Author',
            'version' => '1.0.0',
            'api_version' => '^1.4.0', // '^1.2.0' [TODO]
            'description' => 'Cool description',
            'microfrontend_url' => 'https://front.example.com',
            'icon' => 'https://picsum.photos/200',
            'licence_required' => false,
            'required_permissions' => [],
            'internal_permissions' => [],
        ];

        $this->appData = [
            'url' => $this->url,
            'microfrontend_url' => 'https://front.example.com',
            'name' => 'App name',
            'slug' => Str::slug('App name'),
            'author' => 'Mr. Author',
            'version' => '1.0.0',
            'description' => 'Cool description',
            'icon' => 'https://picsum.photos/200',
        ];
    }

    public function testInstallUnauthorized(): void
    {
        $response = $this->postJson('/apps');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallNotFound($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallFailed($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'product_layout',
                        'description' => 'Setup layouts of products page',
                    ],
                ],
            ])),
            $this->url . '/install' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    public static function invalidResponsesBaseProvider(): iterable
    {
        return DataProvider::list(null, 'not an array', [], ['flat array']);
    }

    public static function invalidResponseProvider(): DataProvider
    {
        return DataProvider::cross(DataProvider::of(self::authProvider()), self::invalidResponsesBaseProvider());
    }

    /**
     * @dataProvider invalidResponseProvider
     */
    public function testInstallInvalidInfo($user, $invalidResponse): void
    {
        $this->{$user}->givePermissionTo('apps.install');

        Http::fake([
            $this->url => Http::response($invalidResponse),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider invalidResponseProvider
     */
    public function testInstallInvalidInstallationResponse($user, $invalidResponse): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
        ]);

        Http::fake([
            $this->url => Http::response($this->fakeResponse),
            $this->url . '/install' => Http::response($invalidResponse),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstall($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'with_description_and_display_name',
                        'display_name' => 'Permission name',
                        'description' => 'Permission description',
                    ],
                    [
                        'name' => 'with_description_and_no_display_name',
                        'display_name' => null,
                        'description' => 'Permission description',
                    ],
                    [
                        'name' => 'with_description',
                        'description' => 'Permission description',
                    ],
                    [
                        'name' => 'null_description',
                        'description' => null,
                    ],
                    [
                        'name' => 'no_description',
                    ],
                ],
            ])),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $name = 'App name';

        $response->assertCreated()
            ->assertJsonFragment(array_merge($this->appData, [
                'metadata' => [],
            ]));

        $this->assertDatabaseHas('apps', array_merge($this->appData, [
            'api_version' => '^1.4.0',
            'uninstall_token' => $uninstallToken,
        ]));

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.with_description_and_display_name',
            'display_name' => 'Permission name',
            'description' => 'Permission description',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.with_description_and_no_display_name',
            'display_name' => null,
            'description' => 'Permission description',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.with_description',
            'display_name' => null,
            'description' => 'Permission description',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.null_description',
            'display_name' => null,
            'description' => null,
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.no_description',
            'display_name' => null,
            'description' => null,
        ]);

        $app = App::where('name', $name)->firstOrFail();

        $this->assertTrue($app->hasAllPermissions([
            'auth.check_identity',
            'products.show',
        ]));

        if ($this->{$user} instanceof User) {
            $this->assertDatabaseHas('roles', [
                'id' => $app->role_id,
                'name' => $name . ' owner',
            ]);

            $this->assertTrue($this->{$user}->hasRole($app->role));
            $this->assertTrue($app->role->hasAllPermissions([
                'app.' . Str::slug($name) . '.with_description',
                'app.' . Str::slug($name) . '.null_description',
                'app.' . Str::slug($name) . '.no_description',
            ]));
        }

        $owner = Role::where('type', RoleType::OWNER)->firstOrFail();
        $this->assertTrue($owner->hasAllPermissions(Permission::all()));
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallUrl($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        $uninstallToken = Str::random(128);

        $url = 'https://example-url.com/';
        Http::fake([
            $url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
            ])),
            $url . 'install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $name = 'App name';

        $response->assertCreated()
            ->assertJsonFragment(array_merge($this->appData, [
                'url' => rtrim($url, '/'),
                'metadata' => [],
            ]));

        $this->assertDatabaseHas('apps', array_merge($this->appData, [
            'url' => rtrim($url, '/'),
            'uninstall_token' => $uninstallToken,
            'api_version' => '^1.4.0',
        ]));

        $app = App::where('name', $name)->firstOrFail();

        $this->assertTrue($app->hasAllPermissions([
            'auth.check_identity',
            'products.show',
        ]));
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallWithMetadata($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response($this->fakeResponse),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/apps', [
                'url' => $this->url,
                'allowed_permissions' => [],
                'public_app_permissions' => [],
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ])->assertCreated()
            ->assertJsonFragment(array_merge($this->appData, [
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]));
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallWithMetadataPrivate($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'apps.show_metadata_private',
            'products.show',
        ]);

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response($this->fakeResponse),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/apps', [
                'url' => $this->url,
                'allowed_permissions' => [],
                'public_app_permissions' => [],
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ])->assertCreated()
            ->assertJsonFragment(array_merge($this->appData, [
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ]));
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallWithOptionalPermissions($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
        ]);

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
                'optional_permissions' => [
                    'products.add',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'product_layout',
                        'description' => 'Setup layouts of products page',
                    ],
                ],
            ])),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertCreated()
            ->assertJsonFragment(array_merge($this->appData, [
                'metadata' => [],
            ]));

        $this->assertDatabaseHas('apps', array_merge($this->appData, [
            'uninstall_token' => $uninstallToken,
            'api_version' => '^1.4.0',
        ]));

        $app = App::where('name', 'App name')->firstOrFail();

        $this->assertTrue($app->hasAllPermissions([
            'auth.check_identity',
            'products.show',
            'products.add',
        ]));
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallWithPublicPermissions($user): void
    {
        $this->{$user}->givePermissionTo('apps.install');

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [],
                'internal_permissions' => [
                    [
                        'name' => 'recommended_public_1',
                        'unauthenticated' => true,
                    ],
                    [
                        'name' => 'recommended_public_2',
                        'unauthenticated' => true,
                    ],
                    [
                        'name' => 'recommended_private_1',
                        'unauthenticated' => false,
                    ],
                    [
                        'name' => 'recommended_private_2',
                        'unauthenticated' => false,
                    ],
                ],
            ])),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [],
            'public_app_permissions' => [
                'recommended_public_1',
                'recommended_private_1',
                'invalid_permission',
            ],
        ]);

        $name = 'App name';

        $response->assertCreated()
            ->assertJsonFragment([
                'url' => $this->url,
                'name' => $name,
                'slug' => Str::slug($name),
                'author' => 'Mr. Author',
                'version' => '1.0.0',
            ]);

        $this->assertDatabaseHas('apps', [
            'url' => $this->url,
            'name' => $name,
            'slug' => Str::slug($name),
            'author' => 'Mr. Author',
            'version' => '1.0.0',
            'api_version' => '^1.4.0',
            'uninstall_token' => $uninstallToken,
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.recommended_public_1',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.recommended_public_2',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.recommended_private_1',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.recommended_private_2',
        ]);

        /** @var Role $unauthenticated */
        $unauthenticated = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $this->assertTrue($unauthenticated->hasAllPermissions([
            'app.' . Str::slug($name) . '.recommended_public_1',
            'app.' . Str::slug($name) . '.recommended_private_1',
        ]));
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallNoInternalPermissions($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        $uninstallToken = Str::random(128);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [],
            ])),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $name = 'App name';
        $response->assertCreated();

        $app = App::where('name', $name)->firstOrFail();
        $this->assertNull($app->role);

        $this->assertDatabaseMissing('roles', [
            'name' => $name . ' owner',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallAssignUnownedPermissions($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallAssignInvalidPermissions($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'nonexistent.permission',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallAppWantsInvalidPermissions($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'nonexistent.permission',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'product_layout',
                        'description' => 'Setup layouts of products page',
                    ],
                ],
            ])),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallNotAssigningRequiredPermissions($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'product_layout',
                        'description' => 'Setup layouts of products page',
                    ],
                ],
            ])),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallExtraPermissions($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
            'products.edit',
        ]);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
                'optional_permissions' => [
                    'products.add',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'product_layout',
                        'description' => 'Setup layouts of products page',
                    ],
                ],
            ])),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
                'products.edit',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallConnectionRefusedRoot($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
            'products.edit',
        ]);

        Http::fake([
            $this->url => new ConnectionException('Test', 7),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
                'products.edit',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallConnectionRefusedInstall($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
            'products.add',
            'products.edit',
        ]);

        Http::retry(0);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'product_layout',
                        'description' => 'Setup layouts of products page',
                    ],
                ],
            ])),
            $this->url . '/install' => new ConnectionException('Test', 7),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
                'products.add',
                'products.edit',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('apps', 1); // +1 from TestCase
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallDuplicateApp($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        App::factory()->create([
            'url' => $this->url . '?query#fragment',
        ]);

        Http::fake([
            $this->url => Http::response($this->fakeResponse),
            $this->url . '/install' => Http::response([
                'uninstall_token' => Str::random(128),
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [],
            'public_app_permissions' => [],
        ]);

        $response->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testReinstall($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
            'apps.remove',
        ]);

        $uninstallToken = Str::random(128);

        $app = App::factory()->create([
            'name' => 'testApp',
            'slug' => 'test',
            'url' => $this->url,
        ]);

        Permission::create([
            'name' => "app.{$app->slug}.test",
            'display_name' => 'test',
            'description' => 'test',
        ]);

        Http::fake([
            $this->url . '/uninstall' => new ConnectionException('Test', 7),
        ]);
        $this->actingAs($this->{$user})->json('delete', '/apps/id:' . $app->getKey(), ['force' => true]);

        Http::fake([
            $this->url => Http::response(array_merge($this->fakeResponse, [
                'name' => 'test',
                'required_permissions' => [
                    'products.show',
                ],
                'internal_permissions' => [
                    [
                        'name' => 'test',
                        'display_name' => 'test',
                        'description' => 'test',
                    ],
                ],
            ])),
            $this->url . '/install' => Http::response([
                'uninstall_token' => $uninstallToken,
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $name = 'test';

        $response->assertCreated()
            ->assertJsonFragment(array_merge($this->appData, [
                'name' => $name,
                'slug' => Str::slug($name),
            ]));

        $this->assertDatabaseHas('apps', array_merge($this->appData, [
            'name' => $name,
            'slug' => Str::slug($name),
            'api_version' => '^1.4.0',
            'uninstall_token' => $uninstallToken,
        ]));

        $this->assertDatabaseHas('permissions', [
            'name' => 'app.' . Str::slug($name) . '.test',
            'display_name' => 'test',
            'description' => 'test',
        ]);

        $app = App::where('name', $name)->firstOrFail();

        $this->assertTrue($app->hasAllPermissions([
            'auth.check_identity',
            'products.show',
        ]));

        if ($this->{$user} instanceof User) {
            $this->assertDatabaseHas('roles', [
                'id' => $app->role_id,
                'name' => $name . ' owner',
            ]);

            $this->assertTrue($this->{$user}->hasRole($app->role));
            $this->assertTrue($app->role->hasAllPermissions([
                'app.' . Str::slug($name) . '.test',
            ]));
        }

        $owner = Role::where('type', RoleType::OWNER)->firstOrFail();
        $this->assertTrue($owner->hasAllPermissions(Permission::all()));
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallAppWithExistingUrl($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
        ]);

        App::factory()->create([
            'url' => $this->url,
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [
                'products.show',
            ],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'App with url: ' . $this->url . ' is already installed']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testInstallTooLongUninstallToken($user): void
    {
        $this->{$user}->givePermissionTo([
            'apps.install',
            'products.show',
        ]);

        Http::fake([
            $this->url => Http::response($this->fakeResponse),
            $this->url . '/install' => Http::response([
                'uninstall_token' => Str::random(256),
            ]),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/apps', [
            'url' => $this->url,
            'allowed_permissions' => [],
            'public_app_permissions' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'App has invalid installation response']);
    }
}
