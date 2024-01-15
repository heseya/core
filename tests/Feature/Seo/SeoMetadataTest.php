<?php

namespace Tests\Feature\Seo;

use Domain\Seo\Models\SeoMetadata;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    private array $expected_structure;
    private SeoMetadata $global_seo;

    public function setUp(): void
    {
        parent::setUp();

        $this->expected_structure = [
            'title',
            'description',
            'keywords',
            'og_image',
            'twitter_card',
            'header_tags',
        ];

        $this->global_seo = SeoMetadata::updateOrCreate(['global' => true], [
            'title' => [$this->lang => 'Title'],
            'description' => [$this->lang => 'Description'],
        ]);

        Cache::forget('seo.global');
    }

    public function testShowUnauthenticated(): void
    {
        $this
            ->json('GET', '/seo')
            ->assertOk()
            ->assertJson(function (AssertableJson $json): void {
                $json
                    ->has('meta', function ($json): void {
                        $json->has('seo')->etc();
                    })
                    ->has('data', function ($json): void {
                        $json
                            ->where('title', $this->global_seo->title)
                            ->where('description', $this->global_seo->description)
                            ->etc();
                    })
                    ->etc();
            })
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow(string $user): void
    {
        $this
            ->actingAs($this->{$user})
            ->json('GET', '/seo')
            ->assertOk()
            ->assertJson(function (AssertableJson $json): void {
                $json
                    ->has('meta', function ($json): void {
                        $json->has('seo')->etc();
                    })
                    ->has('data', function ($json): void {
                        $json
                            ->where('title', $this->global_seo->title)
                            ->where('description', $this->global_seo->description)
                            ->etc();
                    })
                    ->etc();
            });
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowEmptyDatabase(string $user): void
    {
        SeoMetadata::query()->delete();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/seo')
            ->assertOk()
            ->assertJsonFragment(['title' => null]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithTranslationsFlag(string $user): void
    {
        $response = $this->actingAs($this->{$user})->json('GET', '/seo?with_translations=1');

        $expected_structure = array_merge($this->expected_structure, ['translations']);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('meta', fn ($json) => $json->has('seo')
                ->etc())
                ->has('data', fn ($json) => $json->where('title', $this->global_seo->title)
                    ->where('description', $this->global_seo->description)
                    ->etc())
                ->etc())
            ->assertJsonStructure([
                'data' => $expected_structure,
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->json('PATCH', '/seo', [
            'title' => 'title',
            'description' => 'description',
        ]);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateGlobal(string $user): void
    {
        $this->{$user}->givePermissionTo('seo.edit');

        SeoMetadata::query()->where('global', true)->delete();

        $seo = [
            'title' => 'title',
            'description' => 'description',
            'keywords' => ['key', 'words'],
        ];
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/seo', [
                'translations' => [
                    $this->lang => [
                        'title' => 'title',
                        'description' => 'description',
                        'keywords' => ['key', 'words'],
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertCreated()
            ->assertJson(function (AssertableJson $json) use ($seo): void {
                $json
                    ->has('meta', function ($json): void {
                        $json->has('seo')->etc();
                    })
                    ->has('data', function ($json) use ($seo): void {
                        $json
                            ->where('title', $seo['title'])
                            ->where('description', $seo['description'])
                            ->etc();
                    })
                    ->etc();
            })
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);

        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::query()->where('global', true)->first();

        $this->assertEquals(['key', 'words'], $seo->keywords);

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => $seo->title,
            "description->{$this->lang}" => $seo->description,
            'global' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateGlobal(string $user): void
    {
        $this->{$user}->givePermissionTo('seo.edit');

        $seo = [
            'title' => 'title',
            'description' => 'description',
            'keywords' => ['key', 'words'],
        ];
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/seo', [
                'translations' => [
                    $this->lang => [
                        'title' => 'title',
                        'description' => 'description',
                        'keywords' => ['key', 'words'],
                    ],
                ],
                'published' => [$this->lang],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'title' => $seo['title'],
                'description' => $seo['description'],
            ])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);

        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::query()->where('global', true)->first();

        $this->assertEquals(['key', 'words'], $seo->keywords);

        $this->assertDatabaseCount('seo_metadata', 1);

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => $seo->title,
            "description->{$this->lang}" => $seo->description,
            'global' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNoPublished(string $user): void
    {
        $this->{$user}->givePermissionTo('seo.edit');

        $seoGlobal = SeoMetadata::query()->where('global', '=', true)->first();
        $seoGlobal->update([
            'published' => [$this->lang],
        ]);

        $seo = [
            'title' => 'title',
            'description' => 'description',
        ];
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/seo', [
                'translations' => [
                    $this->lang => $seo,
                ],
            ])
            ->assertOk()
            ->assertJsonFragment(array_merge($seo, [
                'published' => [
                    $this->lang,
                ],
            ]))
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);

        $this->assertDatabaseCount('seo_metadata', 1);

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'title',
            "description->{$this->lang}" => 'description',
            'global' => true,
            'published' => json_encode([$this->lang]),
        ]);
    }
}
