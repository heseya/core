<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\OrderDocumentType;
use App\Models\Banner;
use App\Models\BannerMedia;
use App\Models\Media;
use App\Models\Order;
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

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Media::query()->delete();

        Media::factory()->create([
            'type' => MediaType::OTHER,
        ]);

        Media::factory()->create([
            'type' => MediaType::VIDEO,
        ]);

        $response = $this->actingAs($this->$user)->json('GET', '/media');

        $response->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'relations_count' => 0,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredByType($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Media::query()->delete();

        Media::factory()->create([
            'type' => MediaType::OTHER,
        ]);
        Media::factory()->create([
            'type' => MediaType::VIDEO,
        ]);

        $response = $this->actingAs($this->$user)->json('GET', '/media', [
            'type' => MediaType::OTHER,
        ]);

        $response->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'relations_count' => 0,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredByRelations($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Media::query()->delete();

        $product = Product::factory()->create();

        $media = Media::factory()->create([
            'type' => MediaType::OTHER,
        ]);

        Media::factory()->create([
            'type' => MediaType::VIDEO,
        ]);

        $order = Order::factory()->create();
        $order->documents()->save($media, ['type' => OrderDocumentType::INVOICE]);

        $media->products()->save($product);

        $banner = Banner::factory()->create();

        $bannerMedia = BannerMedia::factory()->create([
            'banner_id' => $banner->getKey(),
        ]);
        $bannerMedia->media()->attach($media->getKey(), ['min_screen_width' => 100]);

        $response = $this->actingAs($this->$user)->json('GET', '/media', [
            'has_relationships' => true,
        ]);

        $response->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'relations_count' => 3,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredByRelationsWithNoRelations($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Media::query()->delete();

        $product = Product::factory()->create();

        $media = Media::factory()->create([
            'type' => MediaType::VIDEO,
        ]);
        $media->products()->save($product);

        Media::factory()->create([
            'type' => MediaType::VIDEO,
        ]);

        $response = $this->actingAs($this->$user)->json('GET', '/media?has_relationships=false');

        $response->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'relations_count' => 0,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpload($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);

        $file = UploadedFile::fake()->image('image.jpeg');
        $response = $this->actingAs($this->$user)->postJson('/media', [
            'file' => $file,
            'alt' => 'test',
            'metadata.test' => 'value',
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'type' => MediaType::PHOTO,
                'alt' => 'test',
                'metadata' => [
                    'test' => 'value',
                ],
            ])
            ->assertJsonStructure(['data' => [
                'id',
                'type',
                'url',
                'slug',
                'alt',
                'metadata',
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadPdf($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'doc.pdf']])]);

        $file = UploadedFile::fake()->createWithContent('doc.pdf', 'test pdf content');

        $response = $this->actingAs($this->$user)->postJson('/media', [
            'file' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['type' => MediaType::OTHER])
            ->assertJsonStructure(['data' => [
                'id',
                'type',
                'url',
                'slug',
                'alt',
                'metadata',
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadPagesAdd($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadPagesEdit($user): void
    {
        $this->$user->givePermissionTo('pages.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadProductsAdd($user): void
    {
        $this->$user->givePermissionTo('products.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadProductsEdit($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'image.jpeg']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('image.jpeg'),
        ])->assertCreated();
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeletePagesAdd($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response(status: 204)]);
        $response = $this->actingAs($this->$user)->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $this->media->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeletePagesEdit($user): void
    {
        $this->$user->givePermissionTo('pages.edit');

        Http::fake(['*' => Http::response(status: 204)]);
        $response = $this->actingAs($this->$user)->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $this->media->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteProductsAdd($user): void
    {
        $this->$user->givePermissionTo('products.add');

        Http::fake(['*' => Http::response(status: 204)]);
        $response = $this->actingAs($this->$user)->deleteJson('/media/id:' . $this->media->getKey());
        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $this->media->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteFromProductUnauthorized($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $product = Product::factory()->create();
        $media = Media::factory()->create([
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);
        $product->media()->sync($media);

        $this->actingAs($this->$user)->deleteJson('/media/id:' . $media->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteFromProduct($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $product = Product::factory()->create();
        $media = Media::factory()->create([
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);
        $product->media()->sync($media);

        Http::fake(['*' => Http::response(status: 204)]);
        $this->actingAs($this->$user)->deleteJson('/media/id:' . $media->getKey())
            ->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $media->getKey()]);
    }

    public function videoProvider(): array
    {
        return [
            'as user mp4' => ['user', '.mp4', 'video/mp4'],
            'as user webm' => ['user', '.webm', 'video/webm'],
            'as user ogv' => ['user', '.ogv', 'video/ogg'],
            'as user ogg' => ['user', '.ogg', 'video/ogg'],
            'as user mov' => ['user', '.mov', 'video/quicktime'],
            'as user wmv' => ['user', '.wmv', 'video/x-ms-wmv'],
            'as app mp4' => ['application', '.mp4', 'video/mp4'],
            'as app webm' => ['application', '.webm', 'video/webm'],
            'as app ogv' => ['application', '.ogv', 'video/ogg'],
            'as app ogg' => ['application', '.ogg', 'video/ogg'],
            'as app mov' => ['application', '.mov', 'video/quicktime'],
            'as app wmv' => ['application', '.wmv', 'video/x-ms-wmv'],
        ];
    }

    /**
     * @dataProvider videoProvider
     */
    public function testUploadVideo($user, $extension, $mime): void
    {
        $this->$user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'video' . $extension]])]);

        $file = UploadedFile::fake()->image('video' . $extension);
        $file->mimeTypeToReport = $mime;

        $response = $this->actingAs($this->$user)->postJson('/media', [
            'file' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['type' => MediaType::VIDEO])
            ->assertJsonStructure(['data' => [
                'id',
                'type',
                'url',
            ],
            ]);
    }

    public function invalidVideoProvider(): array
    {
        return [
            'as user avi' => ['user', '.avi', 'video/x-msvideo'],
            'as user ogg audio' => ['user', '.ogg', 'audio/ogg'],
            'as app avi' => ['application', '.avi', 'video/x-msvideo'],
            'as app ogg audio' => ['application', '.ogg', 'audio/ogg'],
        ];
    }

    /**
     * @dataProvider invalidVideoProvider
     */
    public function testUploadInvalidVideo($user, $extension, $mime): void
    {
        $this->$user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'video' . $extension]])]);

        $file = UploadedFile::fake()->image('video' . $extension);
        $file->mimeTypeToReport = $mime;

        $this->actingAs($this->$user)->postJson('/media', [
            'file' => $file,
        ])->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadVideoPagesAdd($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadVideoPagesEdit($user): void
    {
        $this->$user->givePermissionTo('pages.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadVideoProductsAdd($user): void
    {
        $this->$user->givePermissionTo('products.add');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadVideoProductsEdit($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        Http::fake(['*' => Http::response([0 => ['path' => 'video.mp4']])]);
        $this->actingAs($this->$user)->postJson('/media', [
            'file' => UploadedFile::fake()->image('video.mp4'),
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadWithError($user): void
    {
        $this->$user->givePermissionTo('pages.add');
        $file = UploadedFile::fake()->image('video.mp4');

        Http::fake(['*' => Http::response(['message' => 'Bad key'], 500)]);

        $response = $this->actingAs($this->$user)->postJson('/media', [
            'file' => $file,
        ]);

        $response
            ->assertJsonFragment(['message' => 'CDN responded with an error'])
            ->assertStatus(500);
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
