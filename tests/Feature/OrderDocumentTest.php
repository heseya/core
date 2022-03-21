<?php

namespace Tests\Feature;

use App\Enums\OrderDocumentType;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderDocumentTest extends TestCase
{

    public function testDocuments()
    {
        $order = Order::factory()->create();

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);

        $file = UploadedFile::fake()->image('image.jpeg');

        $res = $this->actingAs($this->user)->postJson('orders/id:' . $order->getKey() . '/docs', [
            'file' => $file,
            'type' => OrderDocumentType::OTHER
        ]);

        dd($res->getData());
    }
}
