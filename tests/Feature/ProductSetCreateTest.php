<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Events\ProductSetCreated;
use App\Listeners\WebHookEventListener;
use App\Models\Attribute;
use App\Models\Media;
use App\Models\ProductSet;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class ProductSetCreateTest extends TestCase
{
    private ProductSet $set;
    private ProductSet $privateSet;
    private ProductSet $childSet;
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

        $this->childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $this->set->getKey(),
        ]);

        $this->subChildSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $this->childSet->getKey(),
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
    public function testCreateMinimal($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $set = [
            'name' => 'Test',
        ];

        $defaults = [
            'public' => true,
            'slug' => 'test',
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $defaults + [
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $defaults + [
            'parent_id' => null,
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateTreeViewFalse($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $set = [
            'name' => 'Test',
        ];

        $defaults = [
            'public' => true,
            'slug' => 'test',
        ];

        $this
            ->actingAs($this->$user)
            ->postJson('/product-sets?tree=0', $set + [
                'slug_suffix' => 'test',
                'slug_override' => false,
            ])
            ->assertCreated()
            ->assertJson([
                'data' => $set + $defaults + [
                    'slug_override' => false,
                    'slug_suffix' => 'test',
                    'parent' => null,
                    'children_ids' => [],
                ],
            ])
            ->assertJsonMissing(['data' => 'children']);

        $this->assertDatabaseHas(
            'product_sets',
            $set + $defaults + [
                'parent_id' => null,
            ]
        );

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinimalWithWebHook($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductSetCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $set = [
            'name' => 'Test',
        ];

        $defaults = [
            'public' => true,
            'slug' => 'test',
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $defaults + [
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $defaults + [
            'parent_id' => null,
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductSetCreated;
        });

        $set = ProductSet::find($response->getData()->data->id);

        $event = new ProductSetCreated($set);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $set) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $set->getKey()
                && $payload['data_type'] === 'ProductSet'
                && $payload['event'] === 'ProductSetCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateFull($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);

        $set = [
            'name' => 'Test',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
            'cover_id' => $media->getKey(),
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug_suffix' => 'test',
                'slug_override' => false,
                'parent' => null,
                'slug' => 'test',
                'cover' => [
                    'id' => $media->getKey(),
                    'type' => Str::lower($media->type->key),
                    'url' => $media->url,
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + [
            'parent_id' => null,
            'slug' => 'test',
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateFullWithWebHook($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductSetCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $set = [
            'name' => 'Test',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug_suffix' => 'test',
                'slug_override' => false,
                'parent' => null,
                'slug' => 'test',
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + [
            'parent_id' => null,
            'slug' => 'test',
        ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductSetCreated;
        });

        $set = ProductSet::find($response->getData()->data->id);

        $event = new ProductSetCreated($set);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $set) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $set->getKey()
                && $payload['data_type'] === 'ProductSet'
                && $payload['event'] === 'ProductSetCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateParent($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $set = [
            'name' => 'Test Parent',
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_override' => false,
            'slug_suffix' => 'test-parent',
            'children_ids' => [
                $this->privateSet->getKey(),
                $this->set->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug_override' => false,
                'slug_suffix' => 'test-parent',
                'slug' => 'test-parent',
                'children_ids' => [
//                    $this->privateSet->getKey(),
                    $this->set->getKey(),
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + [
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
    public function testCreateChild($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $parent = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'order' => 15,
        ]);

        $set = [
            'name' => 'Test Child',
        ];

        $parentId = [
            'parent_id' => $parent->getKey(),
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + $parentId + [
            'slug_suffix' => 'test-child',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug' => $parent->slug . '-test-child',
                'slug_suffix' => 'test-child',
                'slug_override' => false,
                'parent' => [
                    'id' => $parent->getKey(),
                    'name' => $parent->name,
                    'slug' => $parent->slug,
                    'slug_suffix' => $parent->slugSuffix,
                    'slug_override' => false,
                    'public' => $parent->public,
                    'visible' => $parent->public && $parent->public_parent,
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => $parent->slug . '-test-child',
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrder($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        ProductSet::factory()->create([
            'public' => true,
            'order' => 20,
        ]);

        $set = [
            'name' => 'Test Order',
        ];

        $order = [
            'order' => 21,
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test-order',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug' => 'test-order',
                'slug_suffix' => 'test-order',
                'slug_override' => false,
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $order + [
            'slug' => 'test-order',
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateChildVisibility($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $parent = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => false,
            'order' => 15,
        ]);

        $set = [
            'name' => 'Test Child',
            'public' => true,
        ];

        $parentId = [
            'parent_id' => $parent->getKey(),
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + $parentId + [
            'slug_suffix' => 'test-child',
            'slug_override' => true,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'visible' => false,
                'slug' => 'test-child',
                'slug_suffix' => 'test-child',
                'slug_override' => true,
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'public_parent' => false,
        ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateDuplicateSlug($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        ProductSet::factory()->create([
            'name' => 'Test duplicate',
            'slug' => 'test-duplicate',
        ]);

        $response = $this->actingAs($this->$user)->postJson('/product-sets', [
            'name' => 'New set',
            'slug_suffix' => 'test-duplicate',
            'slug_override' => false,
        ]);
        $response->assertStatus(422);

        Event::assertNotDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateTreeView($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $child = ProductSet::factory()->create([
            'parent_id' => 'null',
            'name' => 'Child',
            'slug' => 'child',
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'child-grandchild',
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/product-sets?tree=1', [
            'name' => 'New',
            'slug_override' => false,
            'slug_suffix' => 'new',
            'children_ids' => [
                $child->getKey(),
            ],
        ]);
        $parentId = $response->json('data.id');
        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'parent' => null,
                'slug' => 'new',
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children' => [
                    [
                        'id' => $child->getKey(),
                        'name' => 'Child',
                        'slug' => 'new-child',
                        'slug_suffix' => 'child',
                        'slug_override' => false,
                        'public' => true,
                        'visible' => true,
                        'parent_id' => $parentId,
                        'children' => [
                            [
                                'id' => $grandchild->getKey(),
                                'name' => 'Grandchild',
                                'slug' => 'new-child-grandchild',
                                'slug_suffix' => 'grandchild',
                                'slug_override' => false,
                                'public' => true,
                                'visible' => true,
                                'parent_id' => $child->getKey(),
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            ]);

        Event::assertDispatched(ProductSetCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSeo($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $set = [
            'name' => 'Test',
            'public' => false,
        ];

        $media = Media::factory()->create([
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
        ]);

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
            'seo' => [
                'title' => 'seo title',
                'description' => 'seo description',
                'og_image_id' => $media->getKey(),
            ],
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug_suffix' => 'test',
                'slug_override' => false,
                'parent' => null,
                'slug' => 'test',
                'seo' => [
                    'title' => 'seo title',
                    'description' => 'seo description',
                    'og_image' => [
                        'id' => $media->getKey(),
                    ],
                ],
            ],
            ]);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => 'seo title',
            'description' => 'seo description',
            'model_id' => $response->getData()->data->id,
            'model_type' => ProductSet::class,
        ]);

        $this->assertDatabaseHas('product_sets', $set + [
            'parent_id' => null,
            'slug' => 'test',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithAttributes($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $set = [
            'name' => 'Test',
        ];

        $defaults = [
            'public' => true,
            'slug' => 'test',
        ];

        $attrOne = Attribute::factory()->create();
        $attrTwo = Attribute::factory()->create();

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
            'attributes' => [
                $attrOne->getKey(),
                $attrTwo->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $defaults + [
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
            ],
            ]);

        $productSet = ProductSet::find($response->getData()->data->id);

        $this->assertDatabaseHas('product_sets', $set + $defaults + [
            'parent_id' => null,
        ])
            ->assertDatabaseHas('attribute_product_set', [
                'attribute_id' => $attrOne->getKey(),
                'product_set_id' => $productSet->getKey(),
            ])
            ->assertDatabaseHas('attribute_product_set', [
                'attribute_id' => $attrTwo->getKey(),
                'product_set_id' => $productSet->getKey(),
            ]);

        $this->assertTrue($productSet->attributes->contains($attrOne));
        $this->assertTrue($productSet->attributes->contains($attrTwo));

        Event::assertDispatched(ProductSetCreated::class);
    }
}
