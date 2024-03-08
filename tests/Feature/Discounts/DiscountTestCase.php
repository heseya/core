<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryContract;
use Domain\Currency\Currency;
use Domain\ProductSet\ProductSet;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DiscountTestCase extends TestCase
{
    use WithFaker;

    protected array $conditions;
    protected Role $role;
    protected User $conditionUser;
    protected Product $conditionProduct;
    protected ProductSet $conditionProductSet;
    protected array $expectedStructure;
    protected ProductRepositoryContract $productRepository;
    protected Currency $currency;

    public static function timeConditionProvider(): array
    {
        return [
            'as user date between' => [
                'user',
                [
                    'type' => ConditionType::DATE_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '2022-05-09',
                    'end_at' => '2022-05-13',
                ],
            ],
            'as user time between' => [
                'user',
                [
                    'type' => ConditionType::TIME_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '10:00:00',
                    'end_at' => '14:00:00',
                ],
            ],
            'as user weekday in' => [
                'user',
                [
                    'type' => ConditionType::WEEKDAY_IN,
                    'weekday' => [0, 0, 0, 0, 1, 0, 0],
                ],
            ],
            'as app date between' => [
                'application',
                [
                    'type' => ConditionType::DATE_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '2022-05-09',
                    'end_at' => '2022-05-13',
                ],
            ],
            'as app time between' => [
                'application',
                [
                    'type' => ConditionType::TIME_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '10:00:00',
                    'end_at' => '14:00:00',
                ],
            ],
            'as app weekday in' => [
                'application',
                [
                    'type' => ConditionType::WEEKDAY_IN,
                    'weekday' => [0, 0, 0, 0, 1, 0, 0],
                ],
            ],
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = App::make(ProductRepositoryContract::class);
        $this->currency = Currency::DEFAULT;

        // coupons
        Discount::factory()->count(10)->create();
        // sales
        Discount::factory([
            'code' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ])->count(10)->create();

        $this->role = Role::factory()->create();
        $this->conditionUser = User::factory()->create();
        $this->conditionProduct = Product::factory()->create();
        $this->conditionProductSet = ProductSet::factory()->create();

        $this->conditions = [
            [
                'type' => ConditionType::ORDER_VALUE,
                'min_values' => [
                    [
                        'currency' => Currency::PLN->value,
                        'value' => "100.00",
                    ],
                    [
                        'currency' => Currency::GBP->value,
                        'value' => "25.00",
                    ],
                ],
                'max_values' => [
                    [
                        'currency' => Currency::PLN->value,
                        'value' => "500.00",
                    ],
                    [
                        'currency' => Currency::GBP->value,
                        'value' => "125.00",
                    ],
                ],
                'include_taxes' => false,
                'is_in_range' => true,
            ],
            [
                'type' => ConditionType::USER_IN_ROLE,
                'roles' => [
                    $this->role->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::USER_IN,
                'users' => [
                    $this->conditionUser->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::PRODUCT_IN_SET,
                'product_sets' => [
                    $this->conditionProductSet->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::PRODUCT_IN,
                'products' => [
                    $this->conditionProduct->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::DATE_BETWEEN,
                'start_at' => Carbon::now()->toISOString(),
                'end_at' => Carbon::tomorrow()->toISOString(),
                'is_in_range' => true,
            ],
            [
                'type' => ConditionType::TIME_BETWEEN,
                'start_at' => Carbon::now()->toTimeString(),
                'end_at' => Carbon::tomorrow()->toTimeString(),
                'is_in_range' => true,
            ],
            [
                'type' => ConditionType::MAX_USES,
                'max_uses' => 150,
            ],
            [
                'type' => ConditionType::MAX_USES_PER_USER,
                'max_uses' => 5,
            ],
            [
                'type' => ConditionType::WEEKDAY_IN,
                'weekday' => [false, true, false, false, true, true, false],
            ],
            [
                'type' => ConditionType::CART_LENGTH,
                'min_value' => 1,
                'max_value' => 100,
            ],
            [
                'type' => ConditionType::COUPONS_COUNT,
                'min_value' => 1,
                'max_value' => 10,
            ],
        ];

        $this->expectedStructure = [
            'data' => [
                'id',
                'name',
                'description',
                'percentage',
                'amounts',
                'priority',
                'uses',
                'condition_groups',
                'target_type',
                'target_products',
                'target_sets',
                'target_shipping_methods',
                'target_is_allow_list',
                'metadata',
                'active',
            ],
        ];
    }

    protected function assertProductPrices(string $productId, array $priceMatrix): void
    {
        foreach ($priceMatrix as $type => $value) {
            $this->assertDatabaseHas('prices', [
                'model_id' => $productId,
                'price_type' => $type,
                'value' => $value * 100,
            ]);
        }
    }
}
