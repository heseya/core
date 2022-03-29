<?php

namespace Tests\Feature;

use App\Enums\OrderDocumentType;
use App\Events\AddOrderDocument;
use App\Events\SendOrderDocument;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderDocumentTest extends TestCase
{
    private Order $order;
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();


        Event::fake(AddOrderDocument::class);
        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);

        $this->order = Order::factory()->create();
        $this->file = UploadedFile::fake()->image('image.jpeg');
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreDocument($user)
    {
        $this->$user->givePermissionTo('orders.edit');

        $response = $this->actingAs($this->$user)->postJson('orders/id:' . $this->order->getKey() . '/docs', [
            'file' => $this->file,
            'type' => OrderDocumentType::OTHER,
            'name' => 'test',
        ]);

        $response->assertJsonFragment([
            'id' => OrderDocument::all()->first()->getKey(),
            'type' => OrderDocumentType::OTHER,
            'name' => 'test',
        ]);

        $this->assertDatabaseCount('media', 1);
        $this->assertDatabaseCount('order_document', 1);

        Event::assertDispatched(AddOrderDocument::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDocument($user)
    {
        $this->$user->givePermissionTo('orders.edit');

        $this->actingAs($this->$user)->postJson('orders/id:' . $this->order->getKey() . '/docs', [
            'file' => $this->file,
            'type' => OrderDocumentType::OTHER
        ]);

        $response = $this->actingAs($this->$user)
            ->deleteJson(
                'orders/id:' . $this->order->getKey() . '/docs/id:' . $this->order->documents()->latest()->first()->pivot->id
            );

        $response->assertStatus(204);

        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseCount('order_document', 0);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSendDocuments($user)
    {
        Event::fake(SendOrderDocument::class);

        $mediaOne = Media::factory()->create();
        $mediaTwo = Media::factory()->create();

        $this->order->documents()->attach($mediaOne, ['type' => OrderDocumentType::OTHER, 'name' => 'test']);
        $this->order->documents()->attach($mediaTwo, ['type' => OrderDocumentType::OTHER, 'name' => 'test']);

        $response = $this->actingAs($this->$user)->postJson('orders/id:' . $this->order->getKey() . '/docs/send', [
            'uuid' =>
                [
                    $this->order->documents->first()->pivot->id,
                    $this->order->documents->last()->pivot->id,
                ],
        ]);

        $response->assertStatus(JsonResponse::HTTP_NO_CONTENT);

        Event::assertDispatched(SendOrderDocument::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSendOtherOrderDocument($user)
    {
        Event::fake(SendOrderDocument::class);

        $order = Order::factory()->create();

        $mediaOne = Media::factory()->create();
        $mediaTwo = Media::factory()->create();

        $this->order->documents()->attach($mediaOne, ['type' => OrderDocumentType::OTHER, 'name' => 'test']);
        $order->documents()->attach($mediaTwo, ['type' => OrderDocumentType::OTHER, 'name' => 'test']);

        $wrongDocId = $order->documents->last()->pivot->id;

        $response = $this->actingAs($this->$user)->postJson('orders/id:' . $this->order->getKey() . '/docs/send', [
            'uuid' =>
                [
                    $this->order->documents->first()->pivot->id,
                    $wrongDocId,
                ],
        ]);

        $response
            ->assertJsonFragment([
                'message' => 'Document with id '. $wrongDocId . ' doesn\'t belong to this order.'
            ])
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        Event::assertNotDispatched(SendOrderDocument::class);
    }
}
