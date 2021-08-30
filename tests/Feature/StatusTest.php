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
}
