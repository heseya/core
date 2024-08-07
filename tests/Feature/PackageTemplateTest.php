<?php

namespace Tests\Feature;

use App\Models\PackageTemplate;
use Tests\TestCase;

class PackageTemplateTest extends TestCase
{
    private PackageTemplate $package;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->package = PackageTemplate::factory()->create();

        // Expected response
        $this->expected = [
            'id' => $this->package->getKey(),
            'name' => $this->package->name,
            'weight' => $this->package->weight,
            'width' => $this->package->width,
            'height' => $this->package->height,
            'depth' => $this->package->depth,
            'metadata' => [],
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->postJson('/package-templates');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('packages.show');

        $response = $this->actingAs($this->{$user})->getJson('/package-templates');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                0 => $this->expected,
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds($user): void
    {
        $this->{$user}->givePermissionTo('packages.show');

        PackageTemplate::factory()->count(10)->create();

        $response = $this->actingAs($this->{$user})->json('GET', '/package-templates', [
            'ids' => [
                $this->package->getKey(),
            ],
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/package-templates');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->{$user}->givePermissionTo('packages.add');

        $package = [
            'name' => 'Small package',
            'weight' => 1.2,
            'width' => 10,
            'height' => 6,
            'depth' => 2,
        ];

        $response = $this->actingAs($this->{$user})->postJson('/package-templates', $package);
        $response
            ->assertCreated()
            ->assertJson(['data' => $package]);

        $this->assertDatabaseHas('package_templates', $package);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata($user): void
    {
        $this->{$user}->givePermissionTo('packages.add');

        $package = [
            'name' => 'Small package',
            'weight' => 1.2,
            'width' => 10,
            'height' => 6,
            'depth' => 2,
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/package-templates', $package)
            ->assertCreated()
            ->assertJson(['data' => $package]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadataPrivate($user): void
    {
        $this->{$user}->givePermissionTo(['packages.add', 'packages.show_metadata_private']);

        $package = [
            'name' => 'Small package',
            'weight' => 1.2,
            'width' => 10,
            'height' => 6,
            'depth' => 2,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/package-templates', $package)
            ->assertCreated()
            ->assertJson(['data' => $package]);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/package-templates/id:' . $this->package->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('packages.edit');

        $package = [
            'name' => 'PackageTemplate big',
            'weight' => 5.7,
            'width' => 50,
            'height' => 30,
            'depth' => 20,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/package-templates/id:' . $this->package->getKey(),
            $package,
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $package]);

        $this->assertDatabaseHas('package_templates', $package + [
            'id' => $this->package->getKey(),
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/package-templates/id:' . $this->package->getKey());
        $response->assertForbidden();
        $this->assertDatabaseHas('package_templates', $this->package->toArray());
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('packages.remove');

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/package-templates/id:' . $this->package->getKey());
        $response->assertNoContent();
        $this->assertModelMissing($this->package);
    }
}
