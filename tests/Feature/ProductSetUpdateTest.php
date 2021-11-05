<?php

namespace Tests\Feature;

use App\Events\ProductSetUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\ProductSet;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class ProductSetUpdateTest extends TestCase
{
    public function testUpdateUnauthorized(): void
    {
        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'slug' => 'test-edit',
            'public' => false,
            'hide_on_index' => true,
            'parent_id' => null,
            'children_ids' => [],
        ];

        $response = $this->patchJson('/product-sets/id:' . $newSet->getKey(), $set);
        $response->assertForbidden();

        Event::assertNotDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'public' => true,
            'hide_on_index' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/product-sets/id:' . $newSet->getKey(),
            $set + $parentId + [
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ],
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $set + [
                'parent' => null,
                'children_ids' => [],
                'slug' => 'test-edit',
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => 'test-edit',
        ]);

        Event::assertDispatched(ProductSetUpdated::class);
    }

    public function testUpdateWithWebHook(): void
    {
        $this->user->givePermissionTo('product_sets.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ProductSetUpdated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => true,
        ]);

        Bus::fake();

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'public' => true,
            'hide_on_index' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/product-sets/id:' . $newSet->getKey(),
            $set + $parentId + [
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ],
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $set + [
                    'parent' => null,
                    'children_ids' => [],
                    'slug' => 'test-edit',
                    'slug_suffix' => 'test-edit',
                    'slug_override' => false,
                ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
                'slug' => 'test-edit',
            ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ProductSetUpdated;
        });

        $set = ProductSet::find($response->getData()->data->id);

        $event = new ProductSetUpdated($set);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $set) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $set->getKey()
                && $payload['data_type'] === 'ProductSet'
                && $payload['event'] === 'ProductSetUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateParentSlug($user): void
    {
        $this->$user->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $parent = ProductSet::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $child = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'name' => 'Child',
            'slug' => 'parent-child',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'parent-child-grandchild',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->$user)->patchJson(
            '/product-sets/id:' . $parent->getKey(),
            [
                'name' => 'New',
                'public' => true,
                'hide_on_index' => false,
                'parent_id' => null,
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children_ids' => [
                    $child->getKey(),
                ],
            ],
        );
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'parent' => null,
                'slug' => 'new',
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children_ids' => [
                    $child->getKey(),
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $parent->getKey(),
            'slug' => 'new',
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $child->getKey(),
            'slug' => 'new-child',
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $grandchild->getKey(),
            'slug' => 'new-child-grandchild',
        ]);

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateParentSlugTree($user): void
    {
        $this->$user->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $parent = ProductSet::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $child = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'name' => 'Child',
            'slug' => 'parent-child',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'parent-child-grandchild',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->$user)->patchJson(
            '/product-sets/id:' . $parent->getKey() . '?tree',
            [
                'name' => 'New',
                'public' => true,
                'hide_on_index' => false,
                'parent_id' => null,
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children_ids' => [
                    $child->getKey(),
                ],
            ],
        );
        $response
            ->assertOk()
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
                        'hide_on_index' => false,
                        'parent_id' => $parent->getKey(),
                        'children' => [
                            [
                                'id' => $grandchild->getKey(),
                                'name' => 'Grandchild',
                                'slug' => 'new-child-grandchild',
                                'slug_suffix' => 'grandchild',
                                'slug_override' => false,
                                'public' => true,
                                'visible' => true,
                                'hide_on_index' => false,
                                'parent_id' => $child->getKey(),
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            ]);

        Event::assertDispatched(ProductSetUpdated::class);
    }
}
