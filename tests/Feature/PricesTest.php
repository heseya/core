<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\PriceMap\PriceMapService;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PricesTest extends TestCase
{
    private PriceMapService $priceMapService;
    private ProductRepository $productRepository;
    private ProductService $productService;

    private Currency $currency;

    public function setUp(): void
    {
        parent::setUp();

        $this->priceMapService = App::make(PriceMapService::class);
        $this->productRepository = App::make(ProductRepository::class);
        $this->productService = App::make(ProductService::class);
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
    public function testProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = '2500.00';
        $priceMax1 = '3000.00';

        $this->productService->setProductPrices(
            $product1,
            [ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of($priceMax1, $this->currency->value))]]
        );

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin2 = '1000.00';
        $priceMax2 = '1500.00';

        $this->productService->setProductPrices(
            $product2,
            [ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of($priceMax2, $this->currency->value))]]
        );

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
                        'price' => [
                            'currency' => Currency::DEFAULT,
                            'gross' => $priceMin1,
                            'net' => $priceMin1,
                        ],
                    ],
                    [
                        'id' => $product2->getKey(),
                        'price' => [
                            'currency' => Currency::DEFAULT,
                            'gross' => $priceMin2,
                            'net' => $priceMin2,
                        ],
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(14);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsHidden(string $user): void
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
    public function testProductsGeneralDiscount(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;

        $this->priceMapService->updateProductPricesForDefaultMaps($product1, [PriceDto::from(Money::of($priceMin1, $this->currency->value))]);

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

        $this->productService->updateMinPrices($product1);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.price.net', number_format(
                $priceMin1 * (1 - $discountRate),
                2,
                '.',
                '',
            ))
            ->assertJsonPath('data.0.price.gross', number_format(
                $priceMin1 * (1 - $discountRate),
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

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = 2500;

        $this->priceMapService->updateProductPricesForDefaultMaps($product1, [PriceDto::from(Money::of($priceMin1, $this->currency->value))]);

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

        $this->productService->updateMinPrices($product1);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.price.net', number_format(
                $priceMin1 * (1 - $discountRate),
                2,
                '.',
                '',
            ))
            ->assertJsonPath('data.0.price.gross', number_format(
                $priceMin1 * (1 - $discountRate),
                2,
                '.',
                '',
            ));

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
    public function testProductsOtherUserDiscount(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $priceMin1 = '2500.00';

        $this->priceMapService->updateProductPricesForDefaultMaps($product1, [PriceDto::from(Money::of($priceMin1, $this->currency->value))]);

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

        $this->productService->updateMinPrices($product1);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.price.net', $priceMin1);

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

        $this->priceMapService->updateProductPricesForDefaultMaps($product1, [PriceDto::from(Money::of($priceMin1, $this->currency->value))]);

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

        $this->productService->updateMinPrices($product1);

        $this
            ->actingAs($this->user)
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.price.net', number_format(
                $priceMin1 * (1 - $discountRate),
                2,
                '.',
                '',
            ));

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
        $priceMin1 = '2500.00';

        $this->priceMapService->updateProductPricesForDefaultMaps($product1, [PriceDto::from(Money::of($priceMin1, $this->currency->value))]);

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

        $this->productService->updateMinPrices($product1);

        $this
            ->actingAs($this->user)
            ->json('GET', '/prices/products', ['ids' => [
                $product1->getKey(),
            ]])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.price.net', $priceMin1);

        $this->assertQueryCountLessThan(36);
    }
}
