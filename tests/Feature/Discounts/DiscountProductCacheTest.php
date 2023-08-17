<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\Product\ProductPriceType;
use App\Models\Product;
use App\Models\Role;
use App\Repositories\Contracts\ProductRepositoryContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DiscountProductCacheTest extends TestCase
{
    private ProductRepositoryContract $productRepository;
    private Currency $currency;

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = App::make(ProductRepositoryContract::class);
        $this->currency = Currency::DEFAULT;
    }

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
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of($priceMin, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax, $this->currency->value))],
        ]);

        $response = $this
            ->actingAs($this->user)
            ->json('POST', '/sales', $discount + $conditions);

        $response->assertCreated();

        // Assert price didn't decrease
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => $priceMin * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => $priceMax * 100,
        ]);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
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
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of($priceMin, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax, $this->currency->value))],
        ]);

        $response = $this
            ->actingAs($this->user)
            ->json('POST', '/sales', $discount + $conditions);

        $response->assertCreated();

        // Assert price didn't decrease
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => $priceMin * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => $priceMax * 100,
        ]);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws NumberFormatException
     */
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
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of($priceMin, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax, $this->currency->value))],
        ]);

        $response = $this
            ->actingAs($this->user)
            ->json('POST', '/sales', $discount + $conditions);

        $response->assertCreated();

        // Assert price didn't decrease
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => $priceMin * 100,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => $priceMax * 100,
        ]);
    }
}
