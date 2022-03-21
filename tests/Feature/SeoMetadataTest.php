<?php

use App\Models\Product;
use App\Models\SeoMetadata;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    private array $expected_structure;

    public function setUp(): void
    {
        parent::setUp();

        $this->expected_structure = [
            'title',
            'description',
            'keywords',
            'og_image',
            'twitter_card',
        ];
    }

    public function testShowUnauthenticated(): void
    {
        $seo = SeoMetadata::where('global', 1)->first();

        $response = $this->json('GET', '/seo');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('meta', fn ($json) => $json->has('seo')
            ->etc())
            ->has('data', fn ($json) => $json->where('title', $seo->title)
            ->where('description', $seo->description)
            ->etc())
            ->etc())
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $seo = SeoMetadata::where('global', 1)->first();

        $response = $this->actingAs($this->$user)->json('GET', '/seo');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('meta', fn ($json) => $json->has('seo')
            ->etc())
            ->has('data', fn ($json) => $json->where('title', $seo->title)
            ->where('description', $seo->description)
            ->etc())
            ->etc())
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        $seo = [
            'title' => 'title',
            'description' => 'description',
        ];
        $response = $this->json('PATCH', '/seo', $seo);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateGlobal($user): void
    {
        $this->$user->givePermissionTo('seo.edit');

        SeoMetadata::where('global', 1)->delete();

        $seo = [
            'title' => 'title',
            'description' => 'description',
            'keywords' => ['key', 'words'],
        ];
        $response = $this->actingAs($this->$user)->json('PATCH', '/seo', $seo);

        $response->assertCreated()->assertJson(
            fn (AssertableJson $json) => $json->has('meta', fn ($json) => $json->has('seo')
                ->etc())
                ->has('data', fn ($json) => $json->where('title', $seo['title'])
                ->where('description', $seo['description'])
                ->etc())
                ->etc()
        )->assertJsonStructure([
            'data' => $this->expected_structure,
        ]);

        $seo = SeoMetadata::where('global', '=', true)->first();

        $this->assertEquals(['key', 'words'], $seo->keywords);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => $seo->title,
            'description' => $seo->description,
            'global' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateGlobal($user): void
    {
        $this->$user->givePermissionTo('seo.edit');

        $seo2 = [
            'title' => 'title',
            'description' => 'description',
            'keywords' => ['key', 'words'],
        ];
        $response = $this->actingAs($this->$user)->json('PATCH', '/seo', $seo2);

        $response->assertOk()
            ->assertJsonFragment([
                'title' => $seo2['title'],
                'description' => $seo2['description'],
            ])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);

        $seo2 = SeoMetadata::where('global', '=', true)->first();

        $this->assertEquals(['key', 'words'], $seo2->keywords);

        $this->assertDatabaseCount('seo_metadata', 1);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => $seo2->title,
            'description' => $seo2->description,
            'global' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckKeywordsUnauthorized($user): void
    {
        $seo = SeoMetadata::factory([
            'keywords' => [
                'PHP',
                'Laravel',
                'Java',
            ],
        ])->create();

        $this->actingAs($this->$user)->json('POST', '/seo/check', [
            'keywords' => array_merge($seo->keywords, ['Spring Boot']),
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckKeywordsGlobalSeo($user): void
    {
        $this->$user->givePermissionTo('seo.edit');

        $seo = SeoMetadata::where('global', '=', true)->first();

        $seo->update([
            'keywords' => [
                'Global PHP',
                'Global Laravel',
                'Global Java',
            ],
        ]);

        $this->actingAs($this->$user)->json('POST', '/seo/check', [
            'keywords' => [
                'Global PHP',
                'Global Laravel',
                'Global Java',
            ],
        ])->assertOk()->assertJsonFragment(['data' => [
            'duplicated' => false,
            'duplicates' => [],
        ],
        ]);
    }

    public function noDuplicationsProvider(): array
    {
        $different = ['Different1', 'Different2', 'Different3'];
        $less = ['PHP', 'Laravel'];
        return [
            'as user different keywords' => ['user', $different],
            'as user less keywords' => ['user', $less],
            'as user more keywords' => ['user', array_merge($less, ['Java', 'Spring Boot'])],
            'as app different keywords' => ['application', $different],
            'as app less keywords' => ['application', $less],
            'as app more keywords' => ['application', array_merge($less, ['Java', 'Spring Boot'])],
        ];
    }

    /**
     * @dataProvider noDuplicationsProvider
     */
    public function testCheckKeywordsNoDuplicates($user, $keywords): void
    {
        $this->$user->givePermissionTo('seo.edit');

        $product = Product::factory([
            'public' => true,
        ])->create();

        $product->seo()->save(SeoMetadata::factory([
            'keywords' => [
                'PHP',
                'Laravel',
                'Java',
            ],
        ])->create());

        $this->actingAs($this->$user)->json('POST', '/seo/check', [
            'keywords' => $keywords,
        ])->assertOk()->assertJsonFragment(['data' => [
            'duplicated' => false,
            'duplicates' => [],
        ],
        ]);
    }

    public function duplicationsProvider(): array
    {
        $same_order = ['PHP', 'Laravel', 'Java'];
        $different_order = ['Java', 'PHP', 'Laravel'];
        return [
            'as user same order keywords' => ['user', $same_order],
            'as user different order keywords' => ['user', $different_order],
            'as app same order keywords' => ['application', $same_order],
            'as app different order keywords' => ['application', $different_order],
        ];
    }

    /**
     * @dataProvider duplicationsProvider
     */
    public function testCheckKeywordsDuplicates($user, $keywords): void
    {
        $this->$user->givePermissionTo('seo.edit');

        $product = Product::factory([
            'public' => true,
        ])->create();

        $product->seo()->save(SeoMetadata::factory([
            'keywords' => [
                'PHP',
                'Laravel',
                'Java',
            ],
        ])->create());

        $this->actingAs($this->$user)->json('POST', '/seo/check', [
            'keywords' => $keywords,
        ])->assertOk()->assertJsonFragment(['data' => [
            'duplicated' => true,
            'duplicates' => [
                [
                    'id' => $product->getKey(),
                    'model_type' => Str::afterLast($product::class, '\\'),
                ],
            ],
        ],
        ]);
    }

    /**
     * @dataProvider duplicationsProvider
     */
    public function testCheckKeywordsNoDuplicatesExisting($user, $keywords): void
    {
        $this->$user->givePermissionTo('seo.edit');

        $product = Product::factory([
            'public' => true,
        ])->create();

        $product->seo()->save(SeoMetadata::factory([
            'keywords' => [
                'PHP',
                'Laravel',
                'Java',
            ],
        ])->create());

        $this->actingAs($this->$user)->json('POST', '/seo/check', [
            'keywords' => $keywords,
            'excluded' => [
                'id' => $product->getKey(),
                'model' => 'Product',
            ],
        ])->assertOk()->assertJsonFragment(['data' => [
            'duplicated' => false,
            'duplicates' => [],
        ],
        ]);
    }

    /**
     * @dataProvider duplicationsProvider
     */
    public function testCheckKeywordsDuplicatesExisting($user, $keywords): void
    {
        $this->$user->givePermissionTo('seo.edit');

        $product = Product::factory([
            'public' => true,
        ])->create();

        $product->seo()->save(SeoMetadata::factory([
            'keywords' => [
                'PHP',
                'Laravel',
                'Java',
            ],
        ])->create());

        $product2 = Product::factory([
            'public' => true,
        ])->create();

        $product2->seo()->save(SeoMetadata::factory([
            'keywords' => [
                'PHP',
                'Laravel',
                'Java',
            ],
        ])->create());

        $this->actingAs($this->$user)->json('POST', '/seo/check', [
            'keywords' => $keywords,
            'excluded' => [
                'id' => $product->getKey(),
                'model' => 'Product',
            ],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.duplicates')
            ->assertJsonFragment(['data' => [
                'duplicated' => true,
                'duplicates' => [
                    [
                        'id' => $product2->getKey(),
                        'model_type' => Str::afterLast($product2::class, '\\'),
                    ],
                ],
            ],
            ]);
    }
}
