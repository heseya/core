<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\Product;
use Domain\Language\Language;
use Domain\Tag\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexUnauthorized(): void
    {
        $this->getJson('/tags')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexTagsShow($user): void
    {
        $this->{$user}->givePermissionTo('tags.show');

        $this->index($user);
    }

    public function index($user): void
    {
        $tag = Tag::factory()->count(10)->create()->random();

        $product = Product::factory()->create();
        $product->tags()->sync([$tag->getKey()]);

        $response = $this->actingAs($this->{$user})->getJson('/tags');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJson(['data' => [['id' => $tag->getKey()]]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds($user): void
    {
        $this->{$user}->givePermissionTo('tags.show');
        $tag = Tag::factory()->count(10)->create()->random();

        $product = Product::factory()->create();
        $product->tags()->sync([$tag->getKey()]);

        $response = $this->actingAs($this->{$user})->json('GET', '/tags', [
            'ids' => [
                $tag->getKey(),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['id' => $tag->getKey()]]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsAdd($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsEdit($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->index($user);
    }

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/tags')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateTagsAdd($user): void
    {
        $this->{$user}->givePermissionTo('tags.add');

        $this->create($user);
    }

    public function create($user): void
    {
        $this->actingAs($this->{$user})->postJson('/tags?with_translations=1', [
            'translations' => [
                $this->lang => [
                    'name' => 'test sale',
                ],
            ],
            'color' => '444444',
            'published' => [
                $this->lang,
            ],
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'translations' => [
                    $this->lang => [
                        'name' => 'test sale',
                    ],
                ],
                'published' => [
                    $this->lang,
                ],
            ]);

        $this->assertDatabaseHas('tags', [
            "name->{$this->lang}" => 'test sale',
            'color' => '444444',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductsAdd($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->create($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductsEdit($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->create($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithId($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $id = Uuid::uuid4()->toString();

        $response = $this->actingAs($this->{$user})->postJson('/tags', [
            'translations' => [
                $this->lang => [
                    'name' => 'test sale',
                ],
            ],
            'color' => '444444',
            'id' => $id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tags', [
            "name->{$this->lang}" => 'test sale',
            'color' => '444444',
            'id' => $id,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreatePublishTranslationEmptyName($user): void
    {
        $this->{$user}->givePermissionTo('tags.add');

        $this->actingAs($this->{$user})->json('POST', '/tags?with_translations=1', [
            'translations' => [
                $this->lang => [
                    'name' => '',
                ]
            ],
            'color' => 'ababab',
            'published' => [
                $this->lang,
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::PUBLISHING_TRANSLATION_EXCEPTION->name,
                'message' => Exceptions::PUBLISHING_TRANSLATION_EXCEPTION->value . ' in ' . $this->lang,
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $tag = Tag::factory()->create();

        $this->patchJson('/tags/id:' . $tag->getKey())->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();

        $this->actingAs($this->{$user})->patchJson('/tags/id:' . $tag->getKey() . '?with_translations=1', [
            'translations' => [
                $this->lang => [
                    'name' => 'test tag',
                ]
            ],
            'color' => 'ababab',
            'published' => [
                $this->lang,
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'translations' => [
                    $this->lang => [
                        'name' => 'test tag',
                    ]
                ],
                'color' => 'ababab',
                'published' => [
                    $this->lang,
                ],
            ]);

        $this->assertDatabaseHas('tags', [
            "name->{$this->lang}" => 'test tag',
            'color' => 'ababab',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testPublishTranslationEmptyName($user): void
    {
        $this->{$user}->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();

        $this->actingAs($this->{$user})->patchJson('/tags/id:' . $tag->getKey() . '?with_translations=1', [
            'translations' => [
                $this->lang => [
                    'name' => '',
                ]
            ],
            'color' => 'ababab',
            'published' => [
                $this->lang,
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::PUBLISHING_TRANSLATION_EXCEPTION->name,
                'message' => Exceptions::PUBLISHING_TRANSLATION_EXCEPTION->value . ' in ' . $this->lang,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSecondTranslations($user): void
    {
        $this->{$user}->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();

        /** @var Language $en */
        $en = Language::query()->where('iso', '=', 'en')->first();

        $this->actingAs($this->{$user})->patchJson('/tags/id:' . $tag->getKey() . '?with_translations=1', [
            'translations' => [
                $this->lang => [
                    'name' => 'Nowy tag',
                ],
                $en->getKey() => [
                    'name' => 'New tag',
                ],
            ],
            'color' => 'ababab',
            'published' => [
                $this->lang,
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'translations' => [
                    $this->lang => [
                        'name' => 'Nowy tag',
                    ],
                ],
                'color' => 'ababab',
                'published' => [
                    $this->lang,
                ],
                'name' => 'Nowy tag',
            ]);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->getKey(),
            "name->{$this->lang}" => 'Nowy tag',
            "name->{$en->getKey()}" => 'New tag',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithEmptyData($user): void
    {
        $this->{$user}->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->{$user})->patchJson('/tags/id:' . $tag->getKey(), []);

        $response->assertOk();

        $this->assertDatabaseHas('tags', [
            "name->{$this->lang}" => $tag->name,
            'color' => $tag->color,
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $tag = Tag::factory()->create();

        $this->deleteJson('/tags/id:' . $tag->getKey())->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('tags.remove');

        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->{$user})->deleteJson('/tags/id:' . $tag->getKey());

        $response->assertNoContent();

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testTagsTranslationOnProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        /** @var Language $en */
        $en = Language::query()->where('iso', '=', 'en')->first();
        $product = Product::factory()->create([
            'public' => true,
            'published' => $this->lang,
        ]);

        $tagPl = Tag::factory()->create([
            'name' => 'Tag pl',
            'published' => $this->lang,
        ]);
        /** @var Tag $tagEn */
        $tagEn = Tag::factory()->create([
            'name' => 'Tag en',
            'published' => $en->getKey(),
        ]);
        $tagEn->setLocale($en->getKey())->fill(['name' => 'Tag en']);
        $tagEn->save();
        $product->tags()->sync([$tagPl->getKey(), $tagEn->getKey()]);

        $this->actingAs($this->{$user})
            ->json('GET', '/products')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Tag pl',
            ])
            ->assertJsonMissing([
                'name' => 'Tag en',
            ]);
    }
}
