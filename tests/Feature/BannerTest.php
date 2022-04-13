<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Media;
use Tests\TestCase;

class BannerTest extends TestCase
{
    public Banner $banner;
    public array $newBanner = [];
    public array $medias = [];
    public Media $media1;
    public Media $media2;

    public function setUp(): void
    {
        parent::setUp();

        $this->banner = Banner::factory()->create();
        $media = Media::factory()->count(3)->create();

        $rMedia1 = $this->banner->responsiveMedia()->create(['order' => 1]);
        $rMedia1->media()->attach($media[0]->getKey(), ['min_screen_width' => 100]);
        $rMedia1->media()->attach($media[1]->getKey(), ['min_screen_width' => 250]);

        $rMedia2 = $this->banner->responsiveMedia()->create(['order' => 2]);
        $rMedia2->media()->attach($media[2]->getKey(), ['min_screen_width' => 400]);

        $this->media1 = Media::factory()->create();
        $this->media2 = Media::factory()->create();
        $this->newBanner = Banner::factory()->definition();

        $this->medias = [
            'responsive_media' => [
                ['min_screen_width' => 200, 'media' => $this->media1->getKey()],
                ['min_screen_width' => 300, 'media' => $this->media2->getKey()],
            ],
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('banners.show');

        Banner::factory()->count(4)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/banners')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment($this->banner->only(['slug', 'url', 'name', 'active']))
            ->assertJsonFragment(['min_screen_width' => 100])
            ->assertJsonFragment(['min_screen_width' => 250])
            ->assertJsonFragment(['min_screen_width' => 400]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexQuery($user): void
    {
        $this->$user->givePermissionTo('banners.show');

        Banner::factory()->count(4)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/banners?slug=' . $this->banner->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment($this->banner->only(['slug', 'url', 'name', 'active']))
            ->assertJsonFragment(['min_screen_width' => 100])
            ->assertJsonFragment(['min_screen_width' => 250])
            ->assertJsonFragment(['min_screen_width' => 400]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexUnauthorized($user): void
    {
        $this->$user->givePermissionTo('banners.show');

        $this
            ->getJson('/banners')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithoutPermissions($user): void
    {
        $this
            ->actingAs($this->$user)
            ->getJson('/banners')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateBanner($user): void
    {
        $this->$user->givePermissionTo('banners.add');

        $this
            ->actingAs($this->$user)
            ->postJson('/banners', $this->newBanner + $this->medias)
            ->assertCreated()
            ->assertJsonFragment($this->newBanner)
            ->assertJsonFragment([
                'min_screen_width' => $this->medias['responsive_media'][0]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media1->url,
            ])
            ->assertJsonFragment([
                'min_screen_width' => $this->medias['responsive_media'][1]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media2->url,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateBannerUnauthorized($user): void
    {
        $this->$user->givePermissionTo('banners.add');

        $this
            ->postJson('/banners', $this->newBanner)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateBannerWithoutPermissions($user): void
    {
        $this
            ->actingAs($this->$user)
            ->postJson('/banners', $this->newBanner)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateBannerIncompleteData($user): void
    {
        $this->$user->givePermissionTo('banners.add');

        unset($this->newBanner['url']);

        $this
            ->actingAs($this->$user)
            ->postJson('/banners', $this->newBanner)
            ->assertUnprocessable();
    }
}
