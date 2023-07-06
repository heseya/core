<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\MediaAttachmentType;
use App\Enums\MediaType;
use App\Events\AddOrderDocument;
use App\Events\SendOrderDocument;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use App\Models\Status;
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

        $this->order = Order::factory()->create();

        $status = Status::factory()->create([
            'cancel' => false,
        ]);

        $paymentMethod = PaymentMethod::factory()->create();

        $shippingMethod = ShippingMethod::factory()->create();
        $shippingMethod->paymentMethods()->save($paymentMethod);

        $this->order->update([
            'status_id' => $status->getKey(),
            'payment_method_id' => $paymentMethod->getKey(),
            'shipping_method_id' => $shippingMethod->getKey(),
        ]);

        $this->file = UploadedFile::fake()->image('image.jpeg');
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreDocument($user): void
    {
        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);

        $this->{$user}->givePermissionTo('orders.edit');

        $response = $this->actingAs($this->{$user})->postJson('orders/id:' . $this->order->getKey() . '/docs', [
            'file' => $this->file,
            'type' => MediaAttachmentType::OTHER,
            'name' => 'test',
        ]);

        $response->assertJsonFragment([
            'id' => OrderDocument::all()->first()->getKey(),
            'type' => MediaAttachmentType::OTHER,
            'name' => 'test',
        ]);

        $this->assertDatabaseCount('media', 1);
        $this->assertDatabaseCount('order_document', 1);

        Event::assertDispatched(AddOrderDocument::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDocument($user): void
    {
        Http::fake(['*' => Http::response()]);

        $this->{$user}->givePermissionTo('orders.edit');

        $media = Media::factory()->create();

        $this->order->documents()->attach($media, ['type' => MediaAttachmentType::OTHER, 'name' => 'test']);

        $response = $this->actingAs($this->{$user})
            ->deleteJson(
                'orders/id:'
                . $this->order->getKey()
                . '/docs/id:'
                . $this->order->documents()->latest()->first()->pivot->id
            );

        $response->assertStatus(204);

        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseCount('order_document', 0);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSendDocuments($user): void
    {
        Event::fake(SendOrderDocument::class);

        $mediaOne = Media::factory()->create();
        $mediaTwo = Media::factory()->create();

        $this->order->documents()->attach($mediaOne, ['type' => MediaAttachmentType::OTHER, 'name' => 'test']);
        $this->order->documents()->attach($mediaTwo, ['type' => MediaAttachmentType::OTHER, 'name' => 'test']);

        $response = $this->actingAs($this->{$user})->postJson('orders/id:' . $this->order->getKey() . '/docs/send', [
            'uuid' => [
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
    public function testSendOtherOrderDocument($user): void
    {
        Event::fake(SendOrderDocument::class);

        $order = Order::factory()->create();

        $mediaOne = Media::factory()->create();
        $mediaTwo = Media::factory()->create();

        $this->order->documents()->attach($mediaOne, ['type' => MediaAttachmentType::OTHER, 'name' => 'test']);
        $order->documents()->attach($mediaTwo, ['type' => MediaAttachmentType::OTHER, 'name' => 'test']);

        $wrongDocId = $order->documents->last()->pivot->id;

        $response = $this->actingAs($this->{$user})->postJson('orders/id:' . $this->order->getKey() . '/docs/send', [
            'uuid' => [
                $this->order->documents->first()->pivot->id,
                $wrongDocId,
            ],
        ]);

        $response
            ->assertJsonFragment([
                'message' => 'Document with id ' . $wrongDocId . ' doesn\'t belong to this order.',
            ])
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        Event::assertNotDispatched(SendOrderDocument::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDownloadDocument($user): void
    {
        $this->{$user}->givePermissionTo('orders.show_details');

        $file = UploadedFile::fake()->image('test.jpeg');

        $media = Media::factory()->create([
            'type' => MediaType::OTHER,
            'url' => 'silverbox/heseya/test.jpeg',
        ]);

        $this->order->documents()->attach($media, ['type' => MediaAttachmentType::INVOICE, 'name' => 'test']);

        Http::fake(['*' => Http::response($file)]);

        $response = $this->actingAs($this->{$user})
            ->json(
                'GET',
                'orders/id:'
                . $this->order->getKey() . '/docs/id:'
                . $this->order->documents->last()->pivot->id
                . '/download'
            );

        $response
            ->assertStatus(200)
            ->assertHeader('content-disposition', 'attachment; filename=test.jpeg');
    }

    /**
     * @dataProvider authProvider
     */
    public function testDownloadUserDocumentWithoutPermission($user): void
    {
        $this->order->update(['user_id' => $this->{$user}->getKey()]);

        $file = UploadedFile::fake()->image('test.jpeg');

        $media = Media::factory()->create([
            'type' => MediaType::OTHER,
            'url' => 'silverbox/heseya/test.jpeg',
        ]);

        $this->order->documents()->attach($media, ['type' => MediaAttachmentType::INVOICE, 'name' => 'test']);

        Http::fake(['*' => Http::response($file)]);

        $response = $this->actingAs($this->{$user})
            ->json(
                'GET',
                'orders/id:'
                . $this->order->getKey() . '/docs/id:'
                . $this->order->documents->last()->pivot->id
                . '/download'
            );

        $response
            ->assertStatus(422)
            ->assertJsonFragment(
                ['key' => Exceptions::coerce(Exceptions::CLIENT_NO_ACCESS_TO_DOWNLOAD_DOCUMENT)->key],
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testDownloadDocumentUnauthorized($user): void
    {
        $file = UploadedFile::fake()->image('test.jpeg');

        $media = Media::factory()->create([
            'type' => MediaType::OTHER,
            'url' => 'silverbox/heseya/test.jpeg',
        ]);

        $this->order->documents()->attach($media, ['type' => MediaAttachmentType::INVOICE, 'name' => 'test']);

        Http::fake(['*' => Http::response($file)]);

        $response = $this->actingAs($this->{$user})
            ->json(
                'GET',
                'orders/id:'
                . $this->order->getKey() . '/docs/id:'
                . $this->order->documents->last()->pivot->id
                . '/download'
            );

        $response
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => Exceptions::coerce(Exceptions::CLIENT_NO_ACCESS_TO_DOWNLOAD_DOCUMENT)->key,
            ]);
    }
}
