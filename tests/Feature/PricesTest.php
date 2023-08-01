<?php

namespace Tests\Feature;

use App\Dtos\PriceDto;
use App\Enums\ConditionType;
use App\Enums\Currency;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\Product\ProductPriceType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PricesTest extends TestCase
{
    private ProductRepositoryContract $productRepository;
    private Currency $currency;

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = App::make(ProductRepositoryContract::class);
        $this->currency = Currency::DEFAULT;
    }

    public function testProductsUnauthorized(): void
    {
        $this->getJson('/prices/products')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProducts($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin1, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax1, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin2 = 1000;
        $priceMax2 = 1500;
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin2, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax2, $this->currency->value))],
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
                        'price_min' => $priceMin1,
                        'price_max' => $priceMax1,
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $priceMin2,
                        'price_max' => $priceMax2,
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
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsGeneralDiscount($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin1, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax1, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin2 = 1000;
        $priceMax2 = 1500;
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin2, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax2, $this->currency->value))],
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
                        'price_min' => $priceMin1 * (1 - $discountRate),
                        'price_max' => $priceMax1 * (1 - $discountRate),
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $priceMin2 * (1 - $discountRate),
                        'price_max' => $priceMax2 * (1 - $discountRate),
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(32);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsUserDiscount($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin1, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax1, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin2 = 1000;
        $priceMax2 = 1500;
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin2, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax2, $this->currency->value))],
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
                        'price_min' => $priceMin1 * (1 - $discountRate),
                        'price_max' => $priceMax1 * (1 - $discountRate),
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $priceMin2 * (1 - $discountRate),
                        'price_max' => $priceMax2 * (1 - $discountRate),
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(35);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsOtherUserDiscount($user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin1, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax1, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin2 = 1000;
        $priceMax2 = 1500;
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin2, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax2, $this->currency->value))],
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
                        'price_min' => $priceMin1,
                        'price_max' => $priceMax1,
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $priceMin2,
                        'price_max' => $priceMax2,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(35);
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsRoleDiscount(): void
    {
        $this->user->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin1, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax1, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin2 = 1000;
        $priceMax2 = 1500;
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin2, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax2, $this->currency->value))],
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
                        'price_min' => $priceMin1 * (1 - $discountRate),
                        'price_max' => $priceMax1 * (1 - $discountRate),
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $priceMin2 * (1 - $discountRate),
                        'price_max' => $priceMax2 * (1 - $discountRate),
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(36);
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsOtherRoleDiscount(): void
    {
        $this->user->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin1, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax1, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin2 = 1000;
        $priceMax2 = 1500;
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [new PriceDto(Money::of($priceMin2, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [new PriceDto(Money::of($priceMax2, $this->currency->value))],
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
                        'price_min' => $priceMin1,
                        'price_max' => $priceMax1,
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price_min' => $priceMin2,
                        'price_max' => $priceMax2,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(36);
    }
}
