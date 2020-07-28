<?php

namespace Tests\Feature;

use App\Models\Status;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StatusesTest extends TestCase
{
    /**
     * Zmienna status jest zarezerwowana przez TestCase.
     */
    private Status $status_model;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->status_model = factory(Status::class)->create();

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

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->getJson('/statuses');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/statuses');
        $response
            ->assertOk()
            ->assertJsonCount(4, 'data') // domyślne statusy z migracji + ten utworzony teraz
            ->assertJson(['data' => [
                3 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
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
    }

    /**
     * @return void
     */
    public function testUpdate()
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
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->deleteJson('/statuses/id:' . $this->status_model->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/statuses/id:' . $this->status_model->getKey());
        $response->assertNoContent();
    }
}
