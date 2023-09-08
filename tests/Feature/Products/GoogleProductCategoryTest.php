<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

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
            'shipping_digital' => false,
            'prices_base' => $prices,
        ];

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $this->product = $productService->create(FakeDto::productCreateDto());
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
