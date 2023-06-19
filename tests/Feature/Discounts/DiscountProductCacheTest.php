<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\Product;
use App\Models\Role;
use Tests\TestCase;

class DiscountProductCacheTest extends TestCase
{
    public function testDontCacheUserDiscount(): void
    {
        $this->user->givePermissionTo('sales.add');

        $discount = [
            'name' => 'Discount',
            'description' => 'Test discount',
            'value' => 50,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ];

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::USER_IN,
                            'users' => [$this->user->getKey()],
                            'is_allow_list' => true,
                        ],
                    ],
                ],
            ],
        ];

        $priceMin = 100;
        $priceMax = 200;
        $product = Product::factory()->create([
            'public' => true,
            'price' => $priceMin,
            'price_min_initial' => $priceMin,
            'price_max_initial' => $priceMax,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->json('POST', '/sales', $discount + $conditions);

        $response->assertCreated();

        // Assert price didn't decrease
        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => $priceMin,
            'price_max' => $priceMax,
        ]);
    }

    public function testDontCacheRoleDiscount(): void
    {
        $this->user->givePermissionTo('sales.add');

        $discount = [
            'name' => 'Discount',
            'description' => 'Test discount',
            'value' => 50,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ];

        $role = Role::factory()->create();
        $this->user->assignRole($role);

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::USER_IN_ROLE,
                            'roles' => [$role->getKey()],
                            'is_allow_list' => true,
                        ],
                    ],
                ],
            ],
        ];

        $priceMin = 100;
        $priceMax = 200;
        $product = Product::factory()->create([
            'public' => true,
            'price' => $priceMin,
            'price_min_initial' => $priceMin,
            'price_max_initial' => $priceMax,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->json('POST', '/sales', $discount + $conditions);

        $response->assertCreated();

        // Assert price didn't decrease
        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => $priceMin,
            'price_max' => $priceMax,
        ]);
    }

    public function testDontCacheMaxUsesPerUserDiscount(): void
    {
        $this->user->givePermissionTo('sales.add');

        $discount = [
            'name' => 'Discount',
            'description' => 'Test discount',
            'value' => 50,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ];

        $role = Role::factory()->create();
        $this->user->assignRole($role);

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::MAX_USES_PER_USER,
                            'max_uses' => 10,
                        ],
                    ],
                ],
            ],
        ];

        $priceMin = 100;
        $priceMax = 200;
        $product = Product::factory()->create([
            'public' => true,
            'price' => $priceMin,
            'price_min_initial' => $priceMin,
            'price_max_initial' => $priceMax,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->json('POST', '/sales', $discount + $conditions);

        $response->assertCreated();

        // Assert price didn't decrease
        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => $priceMin,
            'price_max' => $priceMax,
        ]);
    }
}
