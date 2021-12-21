<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaUpdateTest extends TestCase
{
    private Media $media;

    public function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'http://cdn.example.com/dev/photo.jpg' => Http::response([
                'path' => 'http://cdn.example.com/dev/test-slug.jpg',
            ]),
        ]);

        $this->media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'http://cdn.example.com/dev/photo.jpg',
            'alt' => null,
            'slug' => null,
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this
            ->json('PATCH', "/media/id:{$this->media->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAlt($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$this->media->getKey()}", [
                'alt' => 'Test alt description',
            ])
            ->assertOk();

        $this->assertDatabaseHas('media', [
            'id' => $this->media->getKey(),
            'alt' => 'Test alt description',
            'slug' => null,
        ]);

        Http::assertNothingSent();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSlug($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$this->media->getKey()}", [
                'slug' => 'test-slug',
            ])
            ->assertOk();

        $this->assertDatabaseHas('media', [
            'id' => $this->media->getKey(),
            'url' => 'http://cdn.example.com/dev/test-slug.jpg',
            'slug' => 'test-slug',
            'alt' => null,
        ]);

        Http::assertSent(function (Request $request) {
            return
                $request->url() === 'http://cdn.example.com/dev/photo.jpg' &&
                $request['slug'] === 'test-slug';
        });
    }
}