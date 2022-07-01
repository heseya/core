<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaUpdateTest extends TestCase
{
    private Media $media;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('silverbox.host', 'http://cdn.example.com');
        Config::set('silverbox.client', 'dev');

        Http::fake([
            'http://cdn.example.com/dev/photo.jpg' => Http::response([
                'path' => 'dev/test-slug.jpg',
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
            ->assertOk()
            ->assertJsonFragment(['alt' => 'Test alt description']);

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
    public function testUpdateSeededMedia($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        $media = Media::factory()->create();

        $res = $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$media->getKey()}", [
                'alt' => 'Test alt description',
            ]);

        dd($res->getData());

//            ->assertJsonFragment(['key' => Exceptions::getKey(Exceptions::SERVER_CDN_ERROR)]);
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
            ->assertOk()
            ->assertJsonFragment(['slug' => 'test-slug'])
            ->assertJsonFragment(['url' => 'http://cdn.example.com/dev/test-slug.jpg']);

        $this->assertDatabaseHas('media', [
            'id' => $this->media->getKey(),
            'url' => 'http://cdn.example.com/dev/test-slug.jpg',
            'slug' => 'test-slug',
            'alt' => null,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'http://cdn.example.com/dev/photo.jpg' &&
                $request['slug'] === 'test-slug';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAltWithNullSlug($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$this->media->getKey()}", [
                'alt' => 'Test alt description',
                'slug' => null,
            ])
            ->assertOk()
            ->assertJsonFragment(['alt' => 'Test alt description']);

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
    public function testUpdateMediaSlugToNull($user): void
    {
        $this->$user->givePermissionTo('pages.add');
        $this->media->update(['slug' => 'test']);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$this->media->getKey()}", [
                'slug' => null,
            ])
            ->assertUnprocessable();

        Http::assertNothingSent();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantUpdateSlugNotUnique($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        Media::create([
            'type' => MediaType::PHOTO,
            'url' => 'http://cdn.example.com/dev/photo.jpg',
            'slug' => 'test-slug',
        ]);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$this->media->getKey()}", [
                'slug' => 'test-slug',
            ])
            ->assertUnprocessable();

        Http::assertNothingSent();
    }

    /**
     * @dataProvider authProvider
     *
     * When user send same slug response should be ok, but request to CDN shouldn't be sent.
     */
    public function testUpdateSameSlug($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        $this->media->update(['slug' => 'test-slug']);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$this->media->getKey()}", [
                'slug' => 'test-slug',
            ])
            ->assertOk();

        Http::assertNothingSent();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateAltNull($user): void
    {
        $this->$user->givePermissionTo('pages.add');

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/media/id:{$this->media->getKey()}", [
                'alt' => null,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'alt' => null,
            ]);

        $this->assertDatabaseHas('media', [
            'id' => $this->media->getKey(),
            'alt' => null,
        ]);

        Http::assertNothingSent();
    }
}
