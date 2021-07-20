<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaTest extends TestCase
{
    private Media $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->media = Media::factory()->create(
            [
                'type' => Media::PHOTO,
                'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
            ]
        );
    }

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

    public function testDeleteByImageUnauthorized(): void
    {
        $response = $this->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertUnauthorized();
    }

    public function testDeleteByImage(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $this->media->getKey()]);
    }
}
