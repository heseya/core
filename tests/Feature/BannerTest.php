<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerMedia;
use App\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class BannerTest extends TestCase
{
    public Banner $banner;
    public BannerMedia $bannerMedia;
    public array $newBanner = [];
    public array $newBannerMedia = [];
    public array $medias = [];
    public Collection $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->banner = Banner::factory()->create();
        $this->media = Media::factory()->count(3)->create();

        $this->bannerMedia = BannerMedia::factory()->create([
            'banner_id' => $this->banner->getKey(),
            'title' => 'abc',
            'subtitle' => 'cba',
            'order' => 1,
        ]);
        $this->bannerMedia->media()->sync([
            $this->media[0]->getKey() => ['min_screen_width' => 100],
            $this->media[1]->getKey() => ['min_screen_width' => 250],
            $this->media[2]->getKey() => ['min_screen_width' => 400],
        ]);

        $rMedia2 = $this->banner->BannerMedia()->create(['order' => 2]);
        $rMedia2->media()->sync([$this->media[2]->getKey() => ['min_screen_width' => 400]]);

        $this->newBanner = Banner::factory()->definition();

        $this->newBannerMedia = BannerMedia::factory()->definition();

        $this->medias = [
            'media' => [
                ['min_screen_width' => 200, 'media' => $this->media[0]->getKey()],
                ['min_screen_width' => 300, 'media' => $this->media[1]->getKey()],
                ['min_screen_width' => 450, 'media' => $this->media[2]->getKey()],
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
            ->assertJsonFragment($this->banner->only(['slug', 'name', 'active']))
            ->assertJsonFragment($this->bannerMedia->only(['title', 'subtitle', 'url']))
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
            ->assertJsonFragment($this->banner->only(['slug', 'name', 'active']))
            ->assertJsonFragment($this->bannerMedia->only(['title', 'subtitle', 'url']))
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
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('banners.show');

        Banner::factory()->count(4)->create();

        $this
            ->actingAs($this->$user)
            ->getJson("/banners/id:{$this->banner->getKey()}")
            ->assertOk()
            ->assertJsonFragment($this->banner->only(['slug', 'name', 'active']))
            ->assertJsonFragment($this->bannerMedia->only(['title', 'subtitle', 'url']))
            ->assertJsonFragment(['min_screen_width' => 100])
            ->assertJsonFragment(['min_screen_width' => 250])
            ->assertJsonFragment(['min_screen_width' => 400]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowNotExistingObject($user): void
    {
        $this->$user->givePermissionTo('banners.show');

        $this->banner->delete();

        $this
            ->actingAs($this->$user)
            ->getJson("/banners/id:{$this->banner->getKey()}")
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowUnauthorized($user): void
    {
        $this->$user->givePermissionTo('banners.show');

        $this
            ->getJson("/banners/id:{$this->banner->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithoutPermissions($user): void
    {
        $this
            ->actingAs($this->$user)
            ->getJson("/banners/id:{$this->banner->getKey()}")
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
            ->postJson('/banners', $this->newBanner + ['banner_media' => [$this->newBannerMedia + $this->medias]])
            ->assertCreated()
            ->assertJsonFragment($this->newBanner)
            ->assertJsonFragment([
                'min_screen_width' => $this->medias['media'][0]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media[0]->url,
            ])
            ->assertJsonFragment([
                'min_screen_width' => $this->medias['media'][1]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media[1]->url,
            ])
            ->assertJsonFragment([
                'min_screen_width' => $this->medias['media'][2]['min_screen_width'],
            ])
            ->assertJsonFragment([
                'url' => $this->media[2]->url,
            ])
            ->assertJsonFragment([
                'title' => $this->newBannerMedia['title'],
                'subtitle' => $this->newBannerMedia['subtitle'],
                'url' => $this->newBannerMedia['url'],
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
            'name' => 'Super spring banner',
            'active' => true,
        ];

        $bannerMedia = [
            'title' => 'changed title',
            'subtitle' => 'new subtitle',
            'url' => 'https://picsum.photos/200',
        ];

        $medias = [
            'media' => [
                ['min_screen_width' => 150, 'media' => $this->media[2]->getKey()],
                ['min_screen_width' => 200, 'media' => $this->media[0]->getKey()],
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson(
                "/banners/id:{$this->banner->getKey()}",
                $banner + ['banner_media' => [$bannerMedia + $medias]]
            )
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
            'name' => 'Super spring banner',
            'active' => true,
        ];

        $response = $this
            ->actingAs($this->$user)
            ->patchJson("/banners/id:{$this->banner->getKey()}", $banner);

        $response
            ->assertJson(['data' => [
                'id' => $response->getData()->data->id,
                'name' => $banner['name'],
                'slug' => $this->banner->slug,
                'active' => $banner['active'],
            ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('banners', [
            'id' => $response->getData()->data->id,
            'name' => $banner['name'],
            'slug' => $this->banner->slug,
            'active' => $banner['active'],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateBannerIncompleteDataWithBannerMedia($user): void
    {
        $this->$user->givePermissionTo('banners.edit');

        BannerMedia::query()->delete();

        $bannerMedia = BannerMedia::factory()->create([
            'banner_id' => $this->banner->id,
            'title' => 'existingmedia',
        ]);

        $media = Media::factory()->create();

        $response = $this
            ->actingAs($this->$user)
            ->patchJson("/banners/id:{$this->banner->getKey()}", [
                'banner_media' => [
                    [
                        'title' => 'test',
                        'media' => [
                            ['min_screen_width' => 150, 'media' => $media->getKey()],
                        ],
                    ],
                ],
            ]);

        $response
            ->assertJson([
                'data' => [
                    'slug' => $this->banner->slug,
                    'name' => $this->banner->name,
                    'active' => $this->banner->active,
                    'banner_media' => [
                        [
                            'url' => null,
                            'title' => 'test',
                            'media' => [
                                [
                                    'min_screen_width' => 150,
                                    'media' => [
                                        'id' => $media->id,
                                        'url' => $media->url,
                                        'slug' => $media->slug,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $data = $response->getData()->data;

        $this
            ->assertDatabaseMissing('banner_media', $bannerMedia->toArray())
            ->assertDatabaseHas('banners', [
                'id' => $data->id,
                'slug' => $data->slug,
                'name' => $data->name,
                'active' => $data->active,
            ])
            ->assertDatabaseHas('banner_media', [
                'id' => $data->banner_media[0]->id,
                'url' => $data->banner_media[0]->url,
                'title' => $data->banner_media[0]->title,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteBanner($user): void
    {
        $this->$user->givePermissionTo('banners.remove');

        $this
            ->actingAs($this->$user)
            ->deleteJson("/banners/id:{$this->banner->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing(Banner::class, [
            'id' => $this->banner->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteBannerUnauthorized($user): void
    {
        $this->$user->givePermissionTo('banners.remove');

        $this
            ->deleteJson("/banners/id:{$this->banner->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteBannerWithoutPermissions($user): void
    {
        $this
            ->actingAs($this->$user)
            ->deleteJson("/banners/id:{$this->banner->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteBannerOnDeletedObject($user): void
    {
        $this->$user->givePermissionTo('banners.remove');

        $this->banner->delete();

        $this
            ->actingAs($this->$user)
            ->deleteJson("/banners/id:{$this->banner->getKey()}")
            ->assertNotFound();
    }
}
