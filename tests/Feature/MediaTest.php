<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaTest extends TestCase
{
    private Media $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);
    }

    public function testUploadUnauthorized(): void
    {
        $response = $this->postJson('/media');
        $response->assertForbidden();
    }

    public function testUpload(): void
    {
        $this->user->givePermissionTo('pages.add');

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

    public function testUploadPagesAdd(): void
    {
        $this->user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    public function testUploadPagesEdit(): void
    {
        $this->user->givePermissionTo('pages.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    public function testUploadProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    public function testUploadProductsEdit(): void
    {
        $this->user->givePermissionTo('products.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertForbidden();
    }

    public function testDeletePagesAdd(): void
    {
        $this->user->givePermissionTo('pages.add');

        $response = $this->actingAs($this->user)->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $this->media->getKey()]);
    }

    public function testDeletePagesEdit(): void
    {
        $this->user->givePermissionTo('pages.edit');

        $response = $this->actingAs($this->user)->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $this->media->getKey()]);
    }

    public function testDeleteProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        $response = $this->actingAs($this->user)->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $this->media->getKey()]);
    }

    public function testDeleteFromProductUnauthorized(): void
    {
        $this->user->givePermissionTo('products.add');

        $product = Product::factory()->create();
        $media = Media::factory()->create([
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);
        $product->media()->sync($media);

        $this->actingAs($this->user)->deleteJson('/media/id:' . $media->getKey())
            ->assertForbidden();
    }

    public function testDeleteFromProduct(): void
    {
        $this->user->givePermissionTo('products.edit');

        $product = Product::factory()->create();
        $media = Media::factory()->create([
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);
        $product->media()->sync($media);

        $this->actingAs($this->user)->deleteJson('/media/id:' . $media->getKey())
            ->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $media->getKey()]);
    }

    public function testUploadVideo(): void
    {
        $this->user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);

        $file = UploadedFile::fake()->image('video.mp4');
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

    public function testUploadVideoPagesAdd(): void
    {
        $this->user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

    public function testUploadVideoPagesEdit(): void
    {
        $this->user->givePermissionTo('pages.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

    public function testUploadVideoProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

    public function testUploadVideoProductsEdit(): void
    {
        $this->user->givePermissionTo('products.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

//    // Uncomment when Pages and media will be related
//
//    public function testDeleteFromPagUnauthorizede(): void
//    {
//        $this->user->givePermissionTo('pages.add');
//
//        $page = Page::factory()->create();
//        $media = Media::factory()->create([
//            'type' => Media::PHOTO,
//            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
//        ]);
//        $page->media()->sync($media);
//
//        $this->actingAs($this->user)->deleteJson('/media/id:' . $media->getKey())
//            ->assertForbidden();
//    }
//
//    public function testDeleteFromPage(): void
//    {
//        $this->user->givePermissionTo('pages.edit');
//
//        $page = Page::factory()->create();
//        $media = Media::factory()->create([
//            'type' => Media::PHOTO,
//            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
//        ]);
//        $page->media()->sync($media);
//
//        $this->actingAs($this->user)->deleteJson('/media/id:' . $media->getKey())
//            ->assertNoContent();
//        $this->assertDatabaseMissing('media', ['id' => $media->getKey()]);
//    }
}
