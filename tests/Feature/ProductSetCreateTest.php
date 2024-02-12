<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\ValidationError;
use App\Listeners\WebHookEventListener;
use App\Models\Media;
use App\Models\Product;
use App\Models\WebHook;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductSet\Events\ProductSetCreated;
use Domain\ProductSet\ProductSet;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class ProductSetCreateTest extends TestCase
{
    private ProductSet $set;
    private ProductSet $privateSet;
    private ProductSet $subChildSet;

    public function setUp(): void
    {
        parent::setUp();

        $this->set = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'order' => 10,
        ]);

        $this->privateSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'order' => 11,
        ]);

        $childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $this->set->getKey(),
        ]);

        $this->subChildSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $childSet->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnauthorized(): void
    {
        Event::fake([ProductSetCreated::class]);

        $set = [
            'name' => 'Test',
            'slug' => 'test',
        ];

        $response = $this->postJson('/product-sets', $set);
        $response->assertForbidden();

        Event::assertNotDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinimal(string $user): void
    {
        Event::fake([ProductSetCreated::class]);

        $defaults = [
            'public' => true,
            'slug' => 'test',
        ];

        $this->{$user}->givePermissionTo('product_sets.add');
        $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'description_html' => null,
                    ],
                ],
                'published' => [$this->lang],
                'public' => true,
                'slug_suffix' => 'test',
                'slug_override' => false,
            ])
            ->assertCreated()
            ->assertJson(['data' => $defaults + [
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
            ]]);

        $this->assertDatabaseHas('product_sets', $defaults + [
            "name->{$this->lang}" => 'Test',
            'parent_id' => null,
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithEmptyChildrenAndAttributes(string $user): void
    {
        Event::fake([ProductSetCreated::class]);

        $defaults = [
            'public' => true,
            'slug' => 'test',
        ];

        $this->{$user}->givePermissionTo('product_sets.add');
        $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'public' => true,
                'slug_suffix' => 'test',
                'slug_override' => false,
                'attributes' => [],
                'children_ids' => [],
            ])
            ->assertCreated()
            ->assertJson(['data' => $defaults + [
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
            ]]);

        $this->assertDatabaseHas('product_sets', $defaults + [
            "name->{$this->lang}" => 'Test',
            'parent_id' => null,
        ],
        );

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithUuid(string $user): void
    {
        $id = Uuid::uuid4()->toString();

        $this->{$user}->givePermissionTo('product_sets.add');
        $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', [
                'id' => $id,
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'public' => true,
                'slug_suffix' => 'test',
                'slug_override' => false,
            ])
            ->assertCreated()
            ->assertJsonFragment(['id' => $id]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $id,
            "name->{$this->lang}" => 'Test',
            'public' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinimalWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductSetCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $defaults = [
            'public' => true,
            'slug' => 'test',
        ];

        $response = $this->actingAs($this->{$user})->postJson('/product-sets', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test Parent',
                ],
            ],
            'published' => [$this->lang],
            'public' => true,
            'slug_suffix' => 'test',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $defaults + [
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
            ]]);

        $this->assertDatabaseHas('product_sets', $defaults + [
            'parent_id' => null,
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductSetCreated;
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateFull(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/product-sets', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test Parent',
                ],
            ],
            'published' => [$this->lang],
            'public' => true,
            'slug_suffix' => 'test',
            'slug_override' => false,
            'cover_id' => $media->getKey(),
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'name' => 'Test Parent',
                'public' => true,
                'slug_suffix' => 'test',
                'slug_override' => false,
                'parent' => null,
                'slug' => 'test',
                'cover' => [
                    'id' => $media->getKey(),
                    'type' => $media->type->value,
                    'url' => $media->url,
                ],
            ]]);

        $this->assertDatabaseHas('product_sets', [
            "name->{$this->lang}" => 'Test Parent',
            'parent_id' => null,
            'slug' => 'test',
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateFullWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductSetCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->{$user})->postJson('/product-sets', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug_suffix' => 'test',
            'slug_override' => false,
            'public' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug_suffix' => 'test',
                'slug_override' => false,
                'parent' => null,
                'slug' => 'test',
            ],
            ]);

        $this->assertDatabaseHas('product_sets', [
            'parent_id' => null,
            'slug' => 'test',
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductSetCreated;
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateParent(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $response = $this->actingAs($this->{$user})->postJson('/product-sets', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test Parent',
                ],
            ],
            'published' => [$this->lang],
            'public' => true,
            'slug_override' => false,
            'slug_suffix' => 'test-parent',
            'children_ids' => [
                $this->privateSet->getKey(),
                $this->set->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'slug_override' => false,
                'slug_suffix' => 'test-parent',
                'slug' => 'test-parent',
                'children_ids' => [
                    //                    $this->privateSet->getKey(),
                    $this->set->getKey(),
                ],
            ]]);

        $this->assertDatabaseHas('product_sets', [
            "name->{$this->lang}" => 'Test Parent',
            'slug' => 'test-parent',
        ]);

        $parentId = $response->json('data.id');

        $this->assertDatabaseHas('product_sets', [
            'id' => $this->privateSet->getKey(),
            'parent_id' => $parentId,
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $this->set->getKey(),
            'parent_id' => $parentId,
            'order' => 1,
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateChild(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        /** @var ProductSet $parent */
        $parent = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'order' => 15,
        ]);

        $parentId = [
            'parent_id' => $parent->getKey(),
        ];

        $response = $this->actingAs($this->{$user})->postJson('/product-sets', $parentId + [
            'translations' => [
                $this->lang => [
                    'name' => 'Test Child',
                ],
            ],
            'published' => [$this->lang],
            'public' => true,
            'slug_suffix' => 'test-child',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'name' => 'Test Child',
                'slug' => $parent->slug . '-test-child',
                'slug_suffix' => 'test-child',
                'slug_override' => false,
                'parent' => [
                    'id' => $parent->getKey(),
                    'name' => $parent->name,
                    'slug' => $parent->slug,
                    'slug_suffix' => $parent->slug_suffix,
                    'slug_override' => false,
                    'public' => $parent->public,
                    'visible' => $parent->public && $parent->public_parent,
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $parentId + [
            "name->{$this->lang}" => 'Test Child',
            'slug' => $parent->slug . '-test-child',
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrder(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        ProductSet::factory()->create([
            'public' => true,
            'order' => 20,
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test Order',
                    ],
                ],
                'public' => true,
                'published' => [$this->lang],
                'slug_suffix' => 'test-order',
                'slug_override' => false,
                'order' => 21,
            ])
            ->assertCreated()
            ->assertJson(['data' => [
                'slug' => 'test-order',
                'slug_suffix' => 'test-order',
                'slug_override' => false,
            ]]);

        $this->assertDatabaseHas('product_sets', [
            'slug' => 'test-order',
            'order' => 21,
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateChildVisibility(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $parent = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => false,
            'order' => 15,
        ]);

        $parentId = [
            'parent_id' => $parent->getKey(),
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', $parentId + [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test Child',
                    ],
                ],
                'published' => [$this->lang],
                'public' => true,
                'slug_suffix' => 'test-child',
                'slug_override' => true,
            ])
            ->assertCreated()
            ->assertJson(['data' => [
                'name' => 'Test Child',
                'visible' => false,
                'slug' => 'test-child',
                'slug_suffix' => 'test-child',
                'slug_override' => true,
            ]]);

        $this->assertDatabaseHas('product_sets', $parentId + [
            "name->{$this->lang}" => 'Test Child',
            'public_parent' => false,
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateDuplicateSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        ProductSet::factory()->create([
            'name' => 'Test duplicate',
            'slug' => 'test-duplicate',
        ]);

        $this->actingAs($this->{$user})->postJson('/product-sets', [
            'name' => 'New set',
            'translations' => [
                $this->lang => [
                    'name' => 'New set',
                ],
            ],
            'slug_suffix' => 'test-duplicate',
            'slug_override' => false,
            'published' => [
                $this->lang,
            ],
            'public' => true,
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => ValidationError::UNIQUE,
                'message' => 'The slug has already been taken.',
            ]);

        Event::assertNotDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateDuplicateSlugDeleted(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $deleted = ProductSet::factory()->create([
            'name' => 'Test duplicate',
            'slug' => 'test-duplicate',
        ]);

        $deleted->delete();

        $this->actingAs($this->{$user})->postJson('/product-sets', [
            'name' => 'New set',
            'translations' => [
                $this->lang => [
                    'name' => 'New set',
                ],
            ],
            'slug_suffix' => 'test-duplicate',
            'slug_override' => false,
            'published' => [
                $this->lang,
            ],
            'public' => true,
        ])->assertCreated();

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSeo(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/product-sets', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'public' => true,
            'slug_suffix' => 'test',
            'slug_override' => false,
            'seo' => [
                'translations' => [
                    $this->lang => [
                        'title' => 'seo title',
                        'description' => 'seo description',
                    ],
                ],
                'published' => [$this->lang],
                'og_image_id' => $media->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Test',
                'slug_suffix' => 'test',
                'slug_override' => false,
                'parent' => null,
                'slug' => 'test',
            ])
            ->assertJsonFragment([
                'title' => 'seo title',
                'description' => 'seo description',
            ])
            ->assertJsonFragment(['id' => $media->getKey()]);

        $set = ProductSet::query()->find($response->json('data.id'))->first();
        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
            'model_id' => $response->json('data.id'),
            'model_type' => $set->getMorphClass(),
        ]);

        $this->assertDatabaseHas('product_sets', [
            "name->{$this->lang}" => 'Test',
            'parent_id' => null,
            'slug' => 'test',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithAttributes(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $attrOne = Attribute::factory()->create();
        $attrTwo = Attribute::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson('/product-sets', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'public' => true,
            'slug_suffix' => 'test',
            'slug_override' => false,
            'attributes' => [
                $attrTwo->getKey(),
                $attrOne->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
                'slug' => 'test',
            ]);

        $productSet = ProductSet::find($response->json('data.id'));

        $this->assertDatabaseHas('product_sets', [
            'parent_id' => null,
            'slug' => 'test',
        ])
            ->assertDatabaseHas('attribute_product_set', [
                'attribute_id' => $attrOne->getKey(),
                'product_set_id' => $productSet->getKey(),
                'order' => 1,
            ])
            ->assertDatabaseHas('attribute_product_set', [
                'attribute_id' => $attrTwo->getKey(),
                'product_set_id' => $productSet->getKey(),
                'order' => 0,
            ]);

        $this->assertTrue($productSet->attributes->contains($attrOne));
        $this->assertTrue($productSet->attributes->contains($attrTwo));

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateNullSlugSuffix(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');
        $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'description_html' => null,
                    ],
                ],
                'published' => [$this->lang],
                'public' => true,
                'slug_suffix' => null,
                'slug_override' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'The slug suffix field is required.',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductDescendant(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        $set1 = ProductSet::factory()->create(['public' => true]);
        $set2 = ProductSet::factory()->create(['public' => true]);

        $product1 = Product::factory()->create(['public' => true]);
        $product2 = Product::factory()->create(['public' => true]);

        $set1->descendantProducts()->attach([
            $product1->getKey() => ['order' => 0],
        ]);

        $set2->descendantProducts()->attach([
            $product2->getKey() => ['order' => 0],
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'description_html' => null,
                    ],
                ],
                'published' => [$this->lang],
                'public' => true,
                'slug_suffix' => 'test',
                'slug_override' => false,
                'children_ids' => [
                    $set1->getKey(),
                    $set2->getKey(),
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'children_ids' => [
                    $set1->getKey(),
                    $set2->getKey(),
                ]
            ]);

        $setId = $response->json('data.id');

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_set_id' => $setId,
            'product_id' => $product1->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_set_id' => $setId,
            'product_id' => $product2->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductDescendantParent(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        $parent = ProductSet::factory()->create(['public' => true]);

        $set1 = ProductSet::factory()->create(['public' => true]);
        $set2 = ProductSet::factory()->create(['public' => true]);

        $product1 = Product::factory()->create(['public' => true]);
        $product2 = Product::factory()->create(['public' => true]);

        $set1->descendantProducts()->attach([
            $product1->getKey() => ['order' => 0],
        ]);

        $set2->descendantProducts()->attach([
            $product2->getKey() => ['order' => 0],
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->postJson('/product-sets', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                        'description_html' => null,
                    ],
                ],
                'published' => [$this->lang],
                'public' => true,
                'slug_suffix' => 'test',
                'slug_override' => false,
                'children_ids' => [
                    $set1->getKey(),
                    $set2->getKey(),
                ],
                'parent_id' => $parent->getKey(),
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'children_ids' => [
                    $set1->getKey(),
                    $set2->getKey(),
                ]
            ]);

        $setId = $response->json('data.id');

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_set_id' => $setId,
            'product_id' => $product1->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_set_id' => $setId,
            'product_id' => $product2->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_set_id' => $parent->getKey(),
            'product_id' => $product1->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product_descendant', [
            'product_set_id' => $parent->getKey(),
            'product_id' => $product2->getKey(),
        ]);
    }
}
