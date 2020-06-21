<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Status;
use Laravel\Passport\Passport;

class StatusesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // zmienna status jest zarezerwowana przez TestCase
        $this->status_model = factory(Status::class)->create();

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->status_model->id,
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
        $response = $this->get('/statuses');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/statuses');
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
        $response = $this->post('/statuses');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $status = [
            'name' => 'Testowy Status',
            'color' => 'ffffff',
            'description' => 'To jest status testowy.',
        ];

        $response = $this->post('/statuses', $status);
        $response
            ->assertCreated()
            ->assertJson(['data' => $status]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/statuses/id:' . $this->status_model->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $status = [
            'name' => 'Status Testowy 2',
            'color' => '444444',
            'description' => 'Testowy opis testowego statusu 2.',
        ];

        $response = $this->patch(
            '/statuses/id:' . $this->status_model->id,
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
        $response = $this->delete('/statuses/id:' . $this->status_model->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/statuses/id:' . $this->status_model->id);
        $response->assertStatus(204);
    }
}
