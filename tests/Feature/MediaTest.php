<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function testUploadUnauthorized(): void
    {
        $response = $this->postJson('/media');
        $response->assertUnauthorized();
    }

    public function testUpload(): void
    {
        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);

        $file = UploadedFile::fake()->image('image.jpeg');

        $response = $this->actingAs($this->user)->postJson('/media', [
            'file' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['data' => [
                'id',
                'type',
                'url',
            ]]);
    }
}
