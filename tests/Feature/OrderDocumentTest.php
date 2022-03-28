<?php

namespace Tests\Feature;

use App\Enums\OrderDocumentType;
use App\Events\AddOrderDocument;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderDocument;
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
}
