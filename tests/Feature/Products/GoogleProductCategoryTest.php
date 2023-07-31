<?php

namespace Tests\Feature\Products;

use App\Dtos\PriceDto;
use App\Dtos\ProductCreateDto;
use App\Enums\Currency;
use App\Models\Product;
use App\Services\Contracts\ProductServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class GoogleProductCategoryTest extends TestCase
{
    private array $request;
    private Product $product;

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $prices = array_map(
            fn (Currency $currency) => [
                'value' => '100.00',
                'currency' => $currency->value,
            ],
            Currency::cases(),
        );

        $this->request = [
            'translations' => [
                $this->lang => [
                    'name' => 'Updated',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'slug',
            'public' => true,
            'shipping_digital' => false,
            'prices_base' => $prices,
        ];

        /** @var ProductServiceContract $productService */
        $productService = App::make(ProductServiceContract::class);
        $this->product = $productService->create(ProductCreateDto::fake());
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithGoogleProductCategory(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'google_product_category' => 123,
                ...$this->request,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'google_product_category' => 123,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWrongGoogleProductCategory(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');
        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'google_product_category' => 123456,
                ...$this->request,
            ])
            ->assertCreated(); // no validation
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithNullGoogleProductCategory(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');
        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'google_product_category' => null,
                ...$this->request,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'google_product_category' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithGoogleProductCategory(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
        $this
            ->actingAs($this->{$user})
            ->patchJson("/products/id:{$this->product->getKey()}", [
                'google_product_category' => 123,
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'google_product_category' => 123,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWrongGoogleProductCategory(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
        $this
            ->actingAs($this->{$user})
            ->patchJson("/products/id:{$this->product->getKey()}", [
                'google_product_category' => 123456789,
            ])
            ->assertOk(); // no validation
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithNullGoogleProductCategory(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
        $this
            ->actingAs($this->{$user})
            ->patchJson("/products/id:{$this->product->getKey()}", [
                'google_product_category' => null,
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'google_product_category' => null,
        ]);
    }
}
