<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class BannerTest extends TestCase
{
    public Banner $banner;
    public array $newBanner = [];
    public array $medias = [];
    public Collection $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->banner = Banner::factory()->create();
        $this->media = Media::factory()->count(3)->create();

        $rMedia1 = $this->banner->responsiveMedia()->create(['order' => 1]);
        $rMedia1->media()->sync([
            $this->media[0]->getKey() => ['min_screen_width' => 100],
            $this->media[1]->getKey() => ['min_screen_width' => 250],
        ]);

        $rMedia2 = $this->banner->responsiveMedia()->create(['order' => 2]);
        $rMedia2->media()->sync([$this->media[2]->getKey() => ['min_screen_width' => 400]]);

        $this->newBanner = Banner::factory()->definition();

        $this->medias = [
            'responsive_media' => [
                [
                    ['min_screen_width' => 200, 'media' => $this->media[0]->getKey()],
                    ['min_screen_width' => 300, 'media' => $this->media[1]->getKey()],
                ],
                [
                    ['min_screen_width' => 150, 'media' => $this->media[2]->getKey()],
                ],
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
                'min_screen_width' => $this->medias['responsive_media'][0][0]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media[0]->url,
            ])
            ->assertJsonFragment([
                'min_screen_width' => $this->medias['responsive_media'][0][1]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media[1]->url,
            ])
            ->assertJsonFragment([
                'min_screen_width' => $this->medias['responsive_media'][1][0]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media[2]->url,
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

    /**
     * @dataProvider authProvider
     */
    public function testUpdateBanner($user): void
    {
        $this->$user->givePermissionTo('banners.edit');

        $banner = [
            'slug' => 'super-spring-banner',
            'url' => 'https://picsum.photos/200',
            'name' => 'Super spring banner',
            'active' => true,
        ];

        $medias = [
            'responsive_media' => [
                [
                    ['min_screen_width' => 150, 'media' => $this->media[2]->getKey()],
                    ['min_screen_width' => 200, 'media' => $this->media[0]->getKey()],
                ],
                [
                    ['min_screen_width' => 300, 'media' => $this->media[1]->getKey()],
                ],
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson("/banners/id:{$this->banner->getKey()}", $banner + $medias)
            ->assertOk()
            ->assertJsonFragment($banner);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateBannerUnauthorized($user): void
    {
        $this->$user->givePermissionTo('banners.edit');

        $banner = [
            'slug' => 'super-spring-banner',
            'url' => 'https://picsum.photos/200',
            'name' => 'Super spring banner',
            'active' => true,
        ];

        $this
            ->patchJson("/banners/id:{$this->banner->getKey()}", $banner + $this->medias)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateBannerWithoutPermissions($user): void
    {
        $banner = [
            'slug' => 'super-spring-banner',
            'url' => 'https://picsum.photos/200',
            'name' => 'Super spring banner',
            'active' => true,
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson("/banners/id:{$this->banner->getKey()}", $banner + $this->medias)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateBannerIncompleteData($user): void
    {
        $this->$user->givePermissionTo('banners.edit');

        $banner = [
            'slug' => 'super-spring-banner',
            'url' => 'https://picsum.photos/200',
            'name' => 'Super spring banner',
            'active' => true,
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson("/banners/id:{$this->banner->getKey()}", $banner)
            ->assertUnprocessable();
    }
}
