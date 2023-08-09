<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\Order;
use App\Models\Status;
use Tests\TestCase;

final class StatusTest extends TestCase
{
    /** $status is used in TestCase. */
    private Status $status_model;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->status_model = Status::factory()->create();

        // Expected response
        $this->expected = [
            'id' => $this->status_model->getKey(),
            'name' => $this->status_model->name,
            'color' => $this->status_model->color,
            'cancel' => false,
            'description' => $this->status_model->description,
            'hidden' => $this->status_model->hidden,
            'no_notifications' => $this->status_model->no_notifications,
            'metadata' => [],
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/statuses');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('statuses.show');

        $response = $this->actingAs($this->{$user})->getJson('/statuses');
        $response
            ->assertOk()
            ->assertJsonCount(4, 'data') // default statuses from migration + the one created now
            ->assertJsonFragment([$this->expected]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds(string $user): void
    {
        $this->{$user}->givePermissionTo('statuses.show');

        $this->actingAs($this->{$user})->json('GET', '/statuses', [
            'ids' => [
                $this->status_model->getKey(),
            ],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([$this->expected]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/statuses');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('statuses.add', 'statuses.show_metadata_private');

        $status = [
            'color' => 'ffffff',
            'hidden' => true,
            'no_notifications' => true,
            'cancel' => false,
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
            'metadata_private' => [
                'attributeMetaPrivate' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/statuses', $status + [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test Status',
                        'description' => 'Test description.',
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertCreated()
            ->assertJson(['data' => $status + [
                'name' => 'Test Status',
                'description' => 'Test description.',
            ]]);

        $this->assertDatabaseHas('statuses', [
            "name->{$this->lang}" => 'Test Status',
            'color' => 'ffffff',
            "description->{$this->lang}" => 'Test description.',
            'hidden' => true,
            'cancel' => false,
            'no_notifications' => true,
        ]);
        $this->assertDatabaseHas('metadata', [
            'name' => 'attributeMeta',
            'value' => 'attributeValue',
        ]);
        $this->assertDatabaseHas('metadata', [
            'name' => 'attributeMetaPrivate',
            'value' => 'attributeValue',
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/statuses/id:' . $this->status_model->getKey());
        $response->assertForbidden();
    }

    public static function statusUpdateProvider(): array
    {
        return [
            'as user cancel false' => ['user', false],
            'as user cancel true' => ['user', true],
            'as app cancel false' => ['application', false],
            'as app cancel true' => ['application', true],
        ];
    }

    /**
     * @dataProvider statusUpdateProvider
     */
    public function testUpdate(string $user, bool $cancel): void
    {
        $this->{$user}->givePermissionTo('statuses.edit');

        $this->status_model->update([
            'cancel' => $cancel,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/statuses/id:' . $this->status_model->getKey(), [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test Status 2',
                        'description' => 'Testowy opis testowego statusu 2.',
                    ],
                ],
                'published' => [$this->lang],
                'color' => '444444',
                'cancel' => !$cancel,
            ])
            ->assertOk()
            ->assertJson(['data' => [
                'name' => 'Test Status 2',
                'color' => '444444',
                'description' => 'Testowy opis testowego statusu 2.',
            ]]);

        $this->assertDatabaseHas('statuses', [
            "name->{$this->lang}" => 'Test Status 2',
            'color' => '444444',
            "description->{$this->lang}" => 'Testowy opis testowego statusu 2.',
        ] + ['id' => $this->status_model->getKey()]);
    }

    /**
     * @dataProvider statusUpdateProvider
     */
    public function testUpdateWhenUsedByOrder($user, bool $cancel): void
    {
        $this->{$user}->givePermissionTo('statuses.edit');

        $this->status_model->update([
            'cancel' => $cancel,
        ]);

        Order::factory()->create([
            'status_id' => $this->status_model->getKey(),
        ]);

        $data = [
            'name' => 'Test Status 2',
            'cancel' => !$cancel,
        ];

        $this
            ->actingAs($this->{$user})
            ->patchJson(
                '/statuses/id:' . $this->status_model->getKey(),
                $data,
            )
            ->assertStatus(422)
            ->assertJsonFragment(['key' => Exceptions::CLIENT_STATUS_USED->name]);

        $this->assertDatabaseHas('statuses', [
            'id' => $this->status_model->getKey(),
            'cancel' => $cancel,
        ]);
    }

    /**
     * @dataProvider statusUpdateProvider
     */
    public function testUpdateWhenUsedByOrderSameCancel(string $user, bool $cancel): void
    {
        $this->{$user}->givePermissionTo('statuses.edit');

        $this->status_model->update([
            'cancel' => $cancel,
        ]);

        Order::factory()->create([
            'status_id' => $this->status_model->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/statuses/id:' . $this->status_model->getKey(), [
                'cancel' => $cancel,
            ])
            ->assertOk();

        $this->assertDatabaseHas('statuses', [
            'id' => $this->status_model->getKey(),
            'cancel' => $cancel,
        ]);
    }

    /**
     * @dataProvider statusUpdateProvider
     */
    public function testUpdateHiddenWhenUsedByOrder(string $user, bool $hidden): void
    {
        $this->{$user}->givePermissionTo('statuses.edit');

        $this->status_model->update([
            'hidden' => $hidden,
        ]);

        Order::factory()->create([
            'status_id' => $this->status_model->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/statuses/id:' . $this->status_model->getKey(), [
                'hidden' => !$hidden,
            ])
            ->assertOk();

        $this->assertDatabaseHas('statuses', [
            'id' => $this->status_model->getKey(),
            'hidden' => !$hidden,
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $this->deleteJson('/statuses/id:' . $this->status_model->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('statuses', ['id' => $this->status_model->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('statuses.remove');

        $this->actingAs($this->{$user})
            ->deleteJson('/statuses/id:' . $this->status_model->getKey())
            ->assertNoContent();

        $this->assertModelMissing($this->status_model);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWhenUsedByOrder(string $user): void
    {
        $this->{$user}->givePermissionTo('statuses.remove');

        Order::factory()->create([
            'status_id' => $this->status_model->getKey(),
        ]);

        $this->actingAs($this->{$user})
            ->deleteJson('/statuses/id:' . $this->status_model->getKey())
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_STATUS_USED->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorderUnauthorized(string $user): void
    {
        $status1 = Status::factory()->create();
        $status2 = Status::factory()->create();
        $status3 = Status::factory()->create();

        $this->actingAs($this->{$user})->json('POST', '/statuses/reorder', [
            'statuses' => [
                $status2->getKey(),
                $status3->getKey(),
                $status1->getKey(),
            ],
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorder(string $user): void
    {
        $this->{$user}->givePermissionTo('statuses.edit');

        $status1 = Status::factory()->create();
        $status2 = Status::factory()->create();
        $status3 = Status::factory()->create();

        $response = $this->actingAs($this->{$user})->json('POST', '/statuses/reorder', [
            'statuses' => [
                $status2->getKey(),
                $status3->getKey(),
                $status1->getKey(),
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseHas('statuses', [
            'id' => $status2->getKey(),
            'order' => 0,
        ]);

        $this->assertDatabaseHas('statuses', [
            'id' => $status3->getKey(),
            'order' => 1,
        ]);

        $this->assertDatabaseHas('statuses', [
            'id' => $status1->getKey(),
            'order' => 2,
        ]);
    }
}
