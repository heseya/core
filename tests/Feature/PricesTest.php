<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use Domain\Price\Enums\ProductPriceType;
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
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PricesTest extends TestCase
{
    private ProductRepositoryContract $productRepository;
    private Currency $currency;
    private SalesChannel $salesChannel;

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = App::make(ProductRepositoryContract::class);
        $this->currency = Currency::DEFAULT;
        $this->salesChannel = app(SalesChannelRepository::class)->getDefault();
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
    public function testProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create();
        $this->salesChannel->products()->attach($product1);
        $priceMin1 = '2500.00';
        $priceMax1 = '3000.00';
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin1, $this->currency->value))->withSalesChannel($this->salesChannel)],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax1, $this->currency->value))->withSalesChannel($this->salesChannel)],
        ]);

        $product2 = Product::factory()->create();
        $this->salesChannel->products()->attach($product2);
        $priceMin2 = '1000.00';
        $priceMax2 = '1500.00';
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin2, $this->currency->value))->withSalesChannel($this->salesChannel)],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax2, $this->currency->value))->withSalesChannel($this->salesChannel)],
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
                        'prices_min' => [[
                            'currency' => Currency::DEFAULT,
                            'gross' => $priceMin1,
                            'net' => $priceMin1,
                            'sales_channel_id' => $this->salesChannel->id,
                        ]],
                        'prices_max' => [[
                            'currency' => Currency::DEFAULT,
                            'gross' => $priceMax1,
                            'net' => $priceMax1,
                            'sales_channel_id' => $this->salesChannel->id,
                        ]],
                    ],
                    [
                        'id' => $product2->getKey(),
                        'prices_min' => [[
                            'currency' => Currency::DEFAULT,
                            'gross' => $priceMin2,
                            'net' => $priceMin2,
                            'sales_channel_id' => $this->salesChannel->id,
                        ]],
                        'prices_max' => [[
                            'currency' => Currency::DEFAULT,
                            'gross' => $priceMax2,
                            'net' => $priceMax2,
                            'sales_channel_id' => $this->salesChannel->id,
                        ]],
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(15);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsHidden(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertQueryCountLessThan(8);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsMissing(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                Str::uuid(),
            ]])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsGeneralDiscount(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create();
        $this->salesChannel->products()->attach($product1);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin1, $this->currency->value))->withSalesChannel($this->salesChannel)],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax1, $this->currency->value))->withSalesChannel($this->salesChannel)],
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'percentage' => $discountRate * 100 . '',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.prices_min.0.net', number_format(
                $priceMin1 * (1 - $discountRate),
                2,
                '.',
                '',
            ))
            ->assertJsonPath('data.0.prices_max.0.net', number_format(
                $priceMax1 * (1 - $discountRate),
                2,
                '.',
                '',
            ));

        $this->assertQueryCountLessThan(21);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsUserDiscount(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create();
        $this->salesChannel->products()->attach($product1);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin1, $this->currency->value))->withSalesChannel($this->salesChannel)],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax1, $this->currency->value))->withSalesChannel($this->salesChannel)],
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'percentage' => $discountRate * 100 . '',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
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
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.prices_min.0.net', number_format(
                $priceMin1 * (1 - $discountRate),
                2,
                '.',
                '',
            ))
            ->assertJsonPath('data.0.prices_max.0.net', number_format(
                $priceMax1 * (1 - $discountRate),
                2,
                '.',
                '',
            ));

        $this->assertQueryCountLessThan(36);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
    public function testProductsOtherUserDiscount(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create();
        $this->salesChannel->products()->attach($product1);

        $priceMin1 = '2500.00';
        $priceMax1 = '3000.00';
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin1, $this->currency->value))->withSalesChannel($this->salesChannel)],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax1, $this->currency->value))->withSalesChannel($this->salesChannel)],
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'percentage' => $discountRate * 100 . '',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
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
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.prices_min.0.net', $priceMin1)
            ->assertJsonPath('data.0.prices_max.0.net', $priceMax1);

        $this->assertQueryCountLessThan(36);
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

        $product1 = Product::factory()->create();
        $this->salesChannel->products()->attach($product1);
        $priceMin1 = 2500;
        $priceMax1 = 3000;
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin1, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax1, $this->currency->value))],
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'percentage' => $discountRate * 100 . '',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
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
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.prices_min.0.net', number_format(
                $priceMin1 * (1 - $discountRate),
                2,
                '.',
                '',
            ))
            ->assertJsonPath('data.0.prices_max.0.net', number_format(
                $priceMax1 * (1 - $discountRate),
                2,
                '.',
                '',
            ));

        $this->assertQueryCountLessThan(37);
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

        $product1 = Product::factory()->create();
        $this->salesChannel->products()->attach($product1);
        $priceMin1 = '2500.00';
        $priceMax1 = '3000.00';
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of($priceMin1, $this->currency->value))->withSalesChannel($this->salesChannel)],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of($priceMax1, $this->currency->value))->withSalesChannel($this->salesChannel)],
        ]);

        $discountRate = 0.5;
        $sale = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja',
            'percentage' => $discountRate * 100 . '',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'priority' => 1,
        ]);

        $sale->products()->attach([
            $product1->getKey(),
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
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.prices_min.0.net', $priceMin1)
            ->assertJsonPath('data.0.prices_max.0.net', $priceMax1);

        $this->assertQueryCountLessThan(37);
    }
}
