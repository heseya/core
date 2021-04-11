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
            'description' => $this->status_model->description,
        ];
    }

    public function testIndex(): void
    {
        $response = $this->getJson('/statuses');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/statuses');
        $response
            ->assertOk()
            ->assertJsonCount(4, 'data') // domyślne statusy z migracji + ten utworzony teraz
            ->assertJsonFragment([$this->expected]);
    }

    public function testCreate(): void
    {
        $response = $this->postJson('/statuses');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $status = [
            'name' => 'Test Status',
            'color' => 'ffffff',
            'description' => 'To jest status testowy.',
        ];

        $response = $this->postJson('/statuses', $status);
        $response
            ->assertCreated()
            ->assertJson(['data' => $status]);

        $this->assertDatabaseHas('statuses', $status);
    }

    public function testUpdate(): void
    {
        $response = $this->patchJson('/statuses/id:' . $this->status_model->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $status = [
            'name' => 'Test Status 2',
            'color' => '444444',
            'description' => 'Testowy opis testowego statusu 2.',
        ];

        $response = $this->patchJson(
            '/statuses/id:' . $this->status_model->getKey(),
            $status,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $status]);

        $this->assertDatabaseHas('statuses', $status + ['id' => $this->status_model->getKey()]);
    }

    public function testDelete(): void
    {
        $response = $this->deleteJson('/statuses/id:' . $this->status_model->getKey());
        $response->assertUnauthorized();
        $this->assertDatabaseHas('statuses', ['id' => $this->status_model->getKey()]);

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/statuses/id:' . $this->status_model->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->status_model);
    }
}
