<?php

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PricesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testProductsUnauthorized(): void
    {
        $this->getJson('/prices/products')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testProducts($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 2000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3000,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 1500,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
                $product2->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $product1->getKey(),
                        'price_min' => $product1->price_min_initial,
                        'price_max' => $product1->price_max_initial,
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $product2->price_min_initial,
                        'price_max' => $product2->price_max_initial,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(15);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsHidden($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsMissing($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                Str::uuid(),
            ]])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsGeneralDiscount($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 2000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3000,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 1500,
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'value' => $discountRate * 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
                $product2->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $product1->getKey(),
                        'price_min' => $product1->price_min_initial * (1 - $discountRate),
                        'price_max' => $product1->price_max_initial * (1 - $discountRate),
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $product2->price_min_initial * (1 - $discountRate),
                        'price_max' => $product2->price_max_initial * (1 - $discountRate),
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(30);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsUserDiscount($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 2000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3000,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 1500,
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'value' => $discountRate * 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $conditionGroup = ConditionGroup::create();
        $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [
                    $this->{$user}->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $sale->conditionGroups()->attach($conditionGroup);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
                $product2->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $product1->getKey(),
                        'price_min' => $product1->price_min_initial * (1 - $discountRate),
                        'price_max' => $product1->price_max_initial * (1 - $discountRate),
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $product2->price_min_initial * (1 - $discountRate),
                        'price_max' => $product2->price_max_initial * (1 - $discountRate),
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(35);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsOtherUserDiscount($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 2000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3000,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 1500,
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'value' => $discountRate * 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $otherUser = User::factory()->create();
        $conditionGroup = ConditionGroup::create();
        $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [
                    $otherUser->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $sale->conditionGroups()->attach($conditionGroup);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
                $product2->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $product1->getKey(),
                        'price_min' => $product1->price_min_initial,
                        'price_max' => $product1->price_max_initial,
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $product2->price_min_initial,
                        'price_max' => $product2->price_max_initial,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(35);
    }

    public function testProductsRoleDiscount(): void
    {
        $this->user->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 2000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3000,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 1500,
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'value' => $discountRate * 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $role = Role::factory()->create();
        $this->user->assignRole($role);

        $conditionGroup = ConditionGroup::create();
        $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [
                    $role->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $sale->conditionGroups()->attach($conditionGroup);

        $this
            ->actingAs($this->user)
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
                $product2->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $product1->getKey(),
                        'price_min' => $product1->price_min_initial * (1 - $discountRate),
                        'price_max' => $product1->price_max_initial * (1 - $discountRate),
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $product2->price_min_initial * (1 - $discountRate),
                        'price_max' => $product2->price_max_initial * (1 - $discountRate),
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(35);
    }

    public function testProductsOtherRoleDiscount(): void
    {
        $this->user->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 2000,
            'price_min_initial' => 2500,
            'price_max_initial' => 3000,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 1500,
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'value' => $discountRate * 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $role = Role::factory()->create();

        $conditionGroup = ConditionGroup::create();
        $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [
                    $role->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $sale->conditionGroups()->attach($conditionGroup);

        $this
            ->actingAs($this->user)
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
                $product2->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'data' => [
                    [
                        'id' => $product1->getKey(),
                        'price_min' => $product1->price_min_initial,
                        'price_max' => $product1->price_max_initial,
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $product2->price_min_initial,
                        'price_max' => $product2->price_max_initial,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(35);
    }
}
