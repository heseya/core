<?php

namespace Tests\Feature;

use App\Models\Status;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StatusTest extends TestCase
{
    /**
     * $status is used in TestCase.
     */
    private Status $status_model;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->status_model = Status::factory()->create();

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->status_model->getKey(),
            'name' => $this->status_model->name,
            'color' => $this->status_model->color,
            'cancel' => false,
            'description' => $this->status_model->description,
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/statuses');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('statuses.show');

        $response = $this->actingAs($this->user)->getJson('/statuses');
        $response
            ->assertOk()
            ->assertJsonCount(4, 'data') // domyślne statusy z migracji + ten utworzony teraz
            ->assertJsonFragment([$this->expected]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/statuses');
        $response->assertForbidden();
    }

    public function testCreate(): void
    {
        $this->user->givePermissionTo('statuses.add');

        $status = [
            'name' => 'Test Status',
            'color' => 'ffffff',
            'description' => 'To jest status testowy.',
        ];

        $response = $this->actingAs($this->user)->postJson('/statuses', $status);
        $response
            ->assertCreated()
            ->assertJson(['data' => $status]);

        $this->assertDatabaseHas('statuses', $status);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/statuses/id:' . $this->status_model->getKey());
        $response->assertForbidden();
    }

    public function testUpdate(): void
    {
        $this->user->givePermissionTo('statuses.edit');

        $status = [
            'name' => 'Test Status 2',
            'color' => '444444',
            'description' => 'Testowy opis testowego statusu 2.',
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/statuses/id:' . $this->status_model->getKey(),
            $status,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $status]);

        $this->assertDatabaseHas('statuses', $status + ['id' => $this->status_model->getKey()]);
    }

    public function testDeleteUnauthorized(): void
    {
        $this->deleteJson('/statuses/id:' . $this->status_model->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('statuses', ['id' => $this->status_model->getKey()]);
    }

    public function testDelete(): void
    {
        $this->user->givePermissionTo('statuses.remove');

        $this->actingAs($this->user)
            ->deleteJson('/statuses/id:' . $this->status_model->getKey())
            ->assertNoContent();

        $this->assertDeleted($this->status_model);
    }

    public function testReorderUnauthorized(): void
    {
        $status1 = Status::factory()->create();
        $status2 = Status::factory()->create();
        $status3 = Status::factory()->create();

        $this->actingAs($this->user)->json('POST', '/statuses/reorder', [
            'statuses' => [
                $status2->getKey(),
                $status3->getKey(),
                $status1->getKey(),
            ]
        ])->assertForbidden();
    }

    public function testReorder(): void
    {
        $this->user->givePermissionTo('statuses.edit');

        $status1 = Status::factory()->create();
        $status2 = Status::factory()->create();
        $status3 = Status::factory()->create();

        $response = $this->actingAs($this->user)->json('POST', '/statuses/reorder', [
           'statuses' => [
               $status2->getKey(),
               $status3->getKey(),
               $status1->getKey(),
           ]
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
