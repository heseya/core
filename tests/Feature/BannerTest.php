<?php

namespace Tests\Feature;

use App\Models\Media;
use Domain\Banner\Models\Banner;
use Domain\Banner\Models\BannerMedia;
use Domain\Language\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class BannerTest extends TestCase
{
  public Banner $banner;
  public BannerMedia $bannerMedia;
  public array $newBanner = [];
  public array $newBannerMedia = [];
  public array $newBannerMediaData = [];
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

    $this->newBannerMediaData = BannerMedia::factory()->definition();
    $this->newBannerMedia = [
      'translations' => [
        $this->lang => [
          'title' => $this->newBannerMediaData['title'],
          'subtitle' => $this->newBannerMediaData['subtitle'],
        ],
      ],
      'url' => $this->newBannerMediaData['url'],
      'order' => $this->newBannerMediaData['order'],
      'published' => [$this->lang],
    ];

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
    $this->{$user}->givePermissionTo('banners.show');

    Banner::factory()->count(4)->create();

    $this
      ->actingAs($this->{$user})
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
  public function testIndexByLanguage($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    $otherLang = Language::query()->whereNot('id', $this->lang)->first()->getKey();

    BannerMedia::query()->delete();
    Banner::query()->delete();

    $banner = Banner::factory()->create();

    $banner->bannerMedia()->create([
      'title' => 'title1',
      'subtitle' => 'subtitle1',
      'url' => 'url1',
      'published' => [$this->lang],
      'order' => 0,
    ]);

    $this
      ->actingAs($this->{$user})
      ->getJson('/banners')
      ->assertJsonCount(1, 'data.0.banner_media');

    $this
      ->actingAs($this->{$user})
      ->getJson('/banners?with_translations=0')
      ->assertJsonCount(1, 'data.0.banner_media');

    $this
      ->actingAs($this->{$user})
      ->getJson('/banners?with_translations=1')
      ->assertJsonCount(1, 'data.0.banner_media');

    Banner::query()->delete();

    $banner = Banner::factory()->create();
    $banner->bannerMedia()->create([
      'title' => 'title1',
      'subtitle' => 'subtitle1',
      'url' => 'url1',
      'published' => [$otherLang],
      'order' => 0,
    ]);

    $this
      ->actingAs($this->{$user})
      ->getJson('/banners')
      ->assertJsonCount(0, 'data.0.banner_media');

    $this
      ->actingAs($this->{$user})
      ->getJson('/banners?with_translations=0')
      ->assertJsonCount(0, 'data.0.banner_media');

    $this
      ->actingAs($this->{$user})
      ->getJson('/banners?with_translations=1')
      ->assertJsonCount(1, 'data.0.banner_media');
  }

  /**
   * @dataProvider authProvider
   */
  public function testIndexByIds($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    Banner::factory()->count(4)->create();

    $this
      ->actingAs($this->{$user})
      ->json('GET', '/banners', [
        'ids' => [
          $this->banner->getKey(),
        ],
      ])
      ->assertOk()
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment($this->banner->only(['slug', 'name', 'active']));
  }

  /**
   * @dataProvider authProvider
   */
  public function testIndexQuery($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    Banner::factory()->count(4)->create();

    $this
      ->actingAs($this->{$user})
      ->getJson('/banners?slug=' . $this->banner->slug)
      ->assertOk()
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment($this->banner->only(['slug', 'name', 'active']))
      ->assertJsonFragment($this->bannerMedia->only(['title', 'subtitle', 'url', 'published']))
      ->assertJsonFragment(['min_screen_width' => 100])
      ->assertJsonFragment(['min_screen_width' => 250])
      ->assertJsonFragment(['min_screen_width' => 400]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testIndexUnauthorized($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

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
      ->actingAs($this->{$user})
      ->getJson('/banners')
      ->assertForbidden();
  }

  /**
   * @dataProvider authProvider
   */
  public function testShow($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    Banner::factory()->count(4)->create();

    $this
      ->actingAs($this->{$user})
      ->getJson("/banners/id:{$this->banner->getKey()}")
      ->assertOk()
      ->assertJsonFragment($this->banner->only(['slug', 'name', 'active']))
      ->assertJsonFragment(
        $this->bannerMedia->only(['title', 'subtitle', 'url']) + ['published' => [$this->lang]]
      )
      ->assertJsonFragment(['min_screen_width' => 100])
      ->assertJsonFragment(['min_screen_width' => 250])
      ->assertJsonFragment(['min_screen_width' => 400]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testShowNotExistingObject($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    $this->banner->delete();

    $this
      ->actingAs($this->{$user})
      ->getJson("/banners/id:{$this->banner->getKey()}")
      ->assertNotFound();
  }

  /**
   * @dataProvider authProvider
   */
  public function testShowUnauthorized($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    $this
      ->getJson("/banners/id:{$this->banner->getKey()}")
      ->assertForbidden();
  }

  /**
   * @dataProvider authProvider
   */
  public function testShowBySlug($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    Banner::factory()->count(4)->create();

    $this
      ->actingAs($this->{$user})
      ->getJson("/banners/{$this->banner->slug}")
      ->assertOk()
      ->assertJsonFragment($this->banner->only(['slug', 'name', 'active']))
      ->assertJsonFragment(
        $this->bannerMedia->only(['title', 'subtitle', 'url']) + ['published' => [$this->lang]]
      )
      ->assertJsonFragment(['min_screen_width' => 100])
      ->assertJsonFragment(['min_screen_width' => 250])
      ->assertJsonFragment(['min_screen_width' => 400]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testShowBySlugNotExistingObject($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    $this->banner->delete();

    $this
      ->actingAs($this->{$user})
      ->getJson("/banners/{$this->banner->slug}")
      ->assertNotFound();
  }

  /**
   * @dataProvider authProvider
   */
  public function testShowBySlugUnauthorized($user): void
  {
    $this->{$user}->givePermissionTo('banners.show');

    $this
      ->getJson("/banners/{$this->banner->slug}")
      ->assertForbidden();
  }

  /**
   * @dataProvider authProvider
   */
  public function testShowWithoutPermissions($user): void
  {
    $this
      ->actingAs($this->{$user})
      ->getJson("/banners/id:{$this->banner->getKey()}")
      ->assertForbidden();
  }

  /**
   * @dataProvider authProvider
   */
  public function testCreateBanner($user): void
  {
    $this->{$user}->givePermissionTo('banners.add');

    $this
      ->actingAs($this->{$user})
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
        'title' => $this->newBannerMediaData['title'],
        'subtitle' => $this->newBannerMediaData['subtitle'],
        'url' => $this->newBannerMediaData['url'],
        'published' => [$this->lang],
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testCreateBannerWithEmptyBannerMedia(string $user): void
  {
    $this->{$user}->givePermissionTo('banners.add');
    $this
      ->actingAs($this->{$user})
      ->postJson('/banners', $this->newBanner + ['banner_media' => []])
      ->assertCreated();
  }

  /**
   * @dataProvider authProvider
   */
  public function testCreateBannerWithMetadata($user): void
  {
    $this->{$user}->givePermissionTo('banners.add');

    $this
      ->actingAs($this->{$user})
      ->postJson(
        '/banners',
        $this->newBanner +
        [
          'banner_media' => [$this->newBannerMedia + $this->medias],
          'metadata' => [
            'attributeMeta' => 'attributeValue',
          ],
        ]
      )
      ->assertCreated()
      ->assertJsonFragment($this->newBanner)
      ->assertJsonFragment([
        'metadata' => [
          'attributeMeta' => 'attributeValue',
        ],
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testCreateBannerWithMetadataPrivate($user): void
  {
    $this->{$user}->givePermissionTo(['banners.add', 'banners.show_metadata_private']);

    $this
      ->actingAs($this->{$user})
      ->postJson(
        '/banners',
        $this->newBanner +
        [
          'banner_media' => [$this->newBannerMedia + $this->medias],
          'metadata_private' => [
            'attributeMetaPriv' => 'attributeValue',
          ],
        ]
      )
      ->assertCreated()
      ->assertJsonFragment($this->newBanner)
      ->assertJsonFragment([
        'metadata_private' => [
          'attributeMetaPriv' => 'attributeValue',
        ],
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testCreateBannerUnauthorized($user): void
  {
    $this->{$user}->givePermissionTo('banners.add');

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
      ->actingAs($this->{$user})
      ->postJson('/banners', $this->newBanner)
      ->assertForbidden();
  }

  /**
   * @dataProvider authProvider
   */
  public function testCreateBannerIncompleteData($user): void
  {
    $this->{$user}->givePermissionTo('banners.add');

    unset($this->newBanner['slug']);

    $this
      ->actingAs($this->{$user})
      ->postJson('/banners', $this->newBanner)
      ->assertUnprocessable();
  }

  /**
   * @dataProvider authProvider
   */
  public function testUpdateBanner($user): void
  {
    $this->{$user}->givePermissionTo('banners.edit');

    $banner = [
      'slug' => 'super-spring-banner',
      'name' => 'Super spring banner',
      'active' => true,
    ];

    $bannerMedia = [
      'id' => $this->bannerMedia->getKey(),
      'translations' => [
        $this->lang => [
          'title' => 'changed title',
          'subtitle' => 'new subtitle',
        ],
      ],
      'url' => 'https://picsum.photos/200',
    ];

    $medias = [
      'media' => [
        ['min_screen_width' => 150, 'media' => $this->media[2]->getKey()],
        ['min_screen_width' => 200, 'media' => $this->media[0]->getKey()],
      ],
    ];

    $this
      ->actingAs($this->{$user})
      ->patchJson(
        "/banners/id:{$this->banner->getKey()}",
        $banner + ['banner_media' => [$bannerMedia + $medias]]
      )
      ->assertOk()
      ->assertJsonFragment($banner)
      ->assertJsonFragment([
        'id' => $this->bannerMedia->getKey(),
        'title' => 'changed title',
        'subtitle' => 'new subtitle',
        'url' => 'https://picsum.photos/200',
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testUpdateBannerDeleteOneBannerMedia($user): void
  {
    $this->{$user}->givePermissionTo('banners.edit');

    $deletedMedia = BannerMedia::factory()->create([
      'banner_id' => $this->banner->getKey(),
      'title' => 'abcd',
      'subtitle' => 'dcba',
      'order' => 2,
    ]);
    $media = Media::factory()->create();
    $deletedMedia->media()->sync([
      $media->getKey() => ['min_screen_width' => 100],
    ]);

    $banner = [
      'slug' => 'super-spring-banner',
      'name' => 'Super spring banner',
      'active' => true,
    ];

    $bannerMedia = [
      'id' => $this->bannerMedia->getKey(),
      'translations' => [
        $this->lang => [
          'title' => 'changed title',
          'subtitle' => 'new subtitle',
        ],
      ],
      'url' => 'https://picsum.photos/200',
    ];

    $medias = [
      'media' => [
        ['min_screen_width' => 150, 'media' => $this->media[2]->getKey()],
        ['min_screen_width' => 200, 'media' => $this->media[0]->getKey()],
      ],
    ];

    $this
      ->actingAs($this->{$user})
      ->patchJson(
        "/banners/id:{$this->banner->getKey()}",
        $banner + ['banner_media' => [$bannerMedia + $medias]]
      )
      ->assertOk()
      ->assertJsonFragment($banner)
      ->assertJsonFragment([
        'id' => $this->bannerMedia->getKey(),
        'title' => 'changed title',
        'subtitle' => 'new subtitle',
        'url' => 'https://picsum.photos/200',
      ])
      ->assertJsonMissing([
        'id' => $deletedMedia->getKey(),
        'title' => 'abcd',
        'subtitle' => 'dcba',
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testUpdateBannerUnauthorized($user): void
  {
    $this->{$user}->givePermissionTo('banners.edit');

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
      ->actingAs($this->{$user})
      ->patchJson("/banners/id:{$this->banner->getKey()}", $banner + $this->medias)
      ->assertForbidden();
  }

  /**
   * @dataProvider authProvider
   */
  public function testUpdateBannerIncompleteData($user): void
  {
    $this->{$user}->givePermissionTo('banners.edit');

    $banner = [
      'name' => 'Super spring banner',
      'active' => true,
    ];

    $response = $this
      ->actingAs($this->{$user})
      ->patchJson("/banners/id:{$this->banner->getKey()}", $banner)
      ->assertOk();

    $response
      ->assertJson([
        'data' => [
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
    $this->{$user}->givePermissionTo('banners.edit');

    BannerMedia::query()->delete();

    $bannerMedia = BannerMedia::factory()->create([
      'banner_id' => $this->banner->id,
      'title' => 'existingmedia',
    ]);

    $media = Media::factory()->create();

    $response = $this
      ->actingAs($this->{$user})
      ->patchJson("/banners/id:{$this->banner->getKey()}", [
        'banner_media' => [
          [
            'translations' => [
              $this->lang => [
                'title' => 'test',
              ],
            ],
            'media' => [
              ['min_screen_width' => 150, 'media' => $media->getKey()],
            ],
            'published' => [
              $this->lang,
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
              'published' => [
                $this->lang,
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
        "title->{$this->lang}" => $data->banner_media[0]->title,
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testUpdateBannerWithBannerMedia($user): void
  {
    $this->{$user}->givePermissionTo('banners.edit');

    BannerMedia::query()->delete();

    $media = Media::factory()->create();

    $response = $this
      ->actingAs($this->{$user})
      ->patchJson("/banners/id:{$this->banner->getKey()}", [
        'banner_media' => [
          [
            'id' => null,
            'translations' => [
              $this->lang => [
                'title' => 'test',
              ],
            ],
            'media' => [
              ['min_screen_width' => 150, 'media' => $media->getKey()],
            ],
            'published' => [
              $this->lang,
            ],
          ],
        ],
      ])
      ->assertOk()
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
              'published' => [
                $this->lang,
              ],
            ],
          ],
        ],
      ]);

    $data = $response->getData()->data;

    $this
      ->assertDatabaseHas('banners', [
        'id' => $data->id,
        'slug' => $data->slug,
        'name' => $data->name,
        'active' => $data->active,
      ])
      ->assertDatabaseHas('banner_media', [
        'id' => $data->banner_media[0]->id,
        'url' => $data->banner_media[0]->url,
        "title->{$this->lang}" => $data->banner_media[0]->title,
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testUpdateBannerSameSlug($user): void
  {
    $this->{$user}->givePermissionTo('banners.edit');

    $banner = [
      'name' => 'Super spring banner',
      'active' => true,
      'slug' => $this->banner->slug,
    ];

    $response = $this
      ->actingAs($this->{$user})
      ->patchJson("/banners/id:{$this->banner->getKey()}", $banner)
      ->assertOk();

    $response
      ->assertJson([
        'data' => [
          'id' => $response->getData()->data->id,
          'name' => $banner['name'],
          'slug' => $this->banner->slug,
          'active' => $banner['active'],
        ],
      ]);

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
  public function testUpdateBannerNoExistingBannerMedia($user): void
  {
    $this->{$user}->givePermissionTo('banners.edit');

    $media = Media::factory()->create();
    $noExistingUUID = Str::uuid();

    $banner = [
      'name' => 'Super spring banner',
      'active' => true,
      'slug' => 'new slug',
      'banner_media' => [
        [
          'id' => $noExistingUUID,
          'translations' => [
            $this->lang => [
              'title' => 'No exist',
            ],
          ],
          'media' => [
            ['min_screen_width' => 150, 'media' => $media->getKey()],
          ],
          'published' => [
            $this->lang,
          ],
        ],
      ],
    ];

    $this
      ->actingAs($this->{$user})
      ->patchJson("/banners/id:{$this->banner->getKey()}", $banner)
      ->assertUnprocessable()
      ->assertJsonFragment([
        'key' => 'VALIDATION_EXISTS',
      ]);
  }

  /**
   * @dataProvider authProvider
   */
  public function testDeleteBanner($user): void
  {
    $this->{$user}->givePermissionTo('banners.remove');

    $this
      ->actingAs($this->{$user})
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
    $this->{$user}->givePermissionTo('banners.remove');

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
      ->actingAs($this->{$user})
      ->deleteJson("/banners/id:{$this->banner->getKey()}")
      ->assertForbidden();
  }

  /**
   * @dataProvider authProvider
   */
  public function testDeleteBannerOnDeletedObject($user): void
  {
    $this->{$user}->givePermissionTo('banners.remove');

    $this->banner->delete();

    $this
      ->actingAs($this->{$user})
      ->deleteJson("/banners/id:{$this->banner->getKey()}")
      ->assertNotFound();
  }
}
