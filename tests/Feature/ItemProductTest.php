<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Heseya\Dto\DtoException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ItemProductTest extends TestCase
{
    private Product $product;
    private Collection $items;
    private array $prices;
    private SalesChannel $salesChannel;

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();
        Product::query()->delete();
        Item::query()->delete();

        $this->salesChannel = app(SalesChannelRepository::class)->getDefault();

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $this->product = $productService->create(FakeDto::productCreateDto());

        $this->items = Item::factory()->count(3)->create();

        $this->prices = array_map(fn (Currency $currency) => [
            'value' => '10.00',
            'currency' => $currency->value,
            'sales_channel_id' => $this->salesChannel->id,
        ], Currency::cases());
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreProductWithItems(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');
        $response = $this->actingAs($this->{$user})->json('POST', '/products', [
            'translations' => [
                $this->lang => ['name' => 'Test'],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $this->prices,
            'shipping_digital' => false,
            'items' => [
                [
                    'id' => $this->items->first()->getKey(),
                    'required_quantity' => 5,
                ],
                [
                    'id' => $this->items->last()->getKey(),
                    'required_quantity' => 15,
                ],
            ],
        ]);
        $response
            ->assertCreated()
            ->assertJsonCount(2, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductItemsCannotSetRequiredQuantityBelowZero(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');
        $this
            ->actingAs($this->{$user})
            ->postJson('/products', [
                'name' => 'test',
                'slug' => 'test',
                'prices_base' => $this->prices,
                'shipping_digital' => false,
                'items' => [
                    [
                        'id' => $this->items->first()->getKey(),
                        'required_quantity' => 0,
                    ],
                ],
            ])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithItems(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices_base' => $this->prices,
            'items' => [
                [
                    'id' => $this->items->first()->getKey(),
                    'required_quantity' => 5,
                ],
                [
                    'id' => $this->items->last()->getKey(),
                    'required_quantity' => 15,
                ],
            ],
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithoutItems(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices_base' => $this->prices,
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithEmptyItems(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices_base' => $this->prices,
            'items' => [],
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
        $this->assertDatabaseCount('item_product', 0);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithItemsOverride(string $user): void
    {
        $this->product->items()->attach($this->items->get(0)->getKey(), ['required_quantity' => 5]);
        $this->product->items()->attach($this->items->get(1)->getKey(), ['required_quantity' => 15]);

        $this->{$user}->givePermissionTo('products.edit');
        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices_base' => $this->prices,
            'items' => [
                [
                    'id' => $this->items->get(2)->getKey(),
                    'required_quantity' => 20,
                ],
            ],
        ]);

        $response->assertValid()
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this
            ->assertDatabaseCount('item_product', 1)
            ->assertDatabaseHas('item_product', [
                'item_id' => $this->items->get(2)->getKey(),
                'product_id' => $this->product->getKey(),
                'required_quantity' => 20,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithClearItems(string $user): void
    {
        $this->product->items()->attach($this->items->get(0)->getKey(), ['required_quantity' => 5]);
        $this->product->items()->attach($this->items->get(1)->getKey(), ['required_quantity' => 15]);

        $this->{$user}->givePermissionTo('products.edit');
        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices_base' => $this->prices,
            'items' => [],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this
            ->assertDatabaseCount('item_product', 0)
            ->assertDatabaseMissing('item_product', [
                'item_id' => $this->items->get(0)->getKey(),
                'product_id' => $this->product->getKey(),
                'required_quantity' => 5,
            ])
            ->assertDatabaseMissing('item_product', [
                'item_id' => $this->items->get(1)->getKey(),
                'product_id' => $this->product->getKey(),
                'required_quantity' => 15,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithoutItemsOverride(string $user): void
    {
        $this->product->items()->attach($this->items->get(0)->getKey(), ['required_quantity' => 5]);
        $this->product->items()->attach($this->items->get(1)->getKey(), ['required_quantity' => 15]);

        $this->{$user}->givePermissionTo('products.edit');

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices_base' => $this->prices,
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.items');

        $this
            ->assertDatabaseCount('item_product', 2)
            ->assertDatabaseHas('item_product', [
                'item_id' => $this->items->get(0)->getKey(),
                'product_id' => $this->product->getKey(),
                'required_quantity' => 5,
            ])
            ->assertDatabaseHas('item_product', [
                'item_id' => $this->items->get(1)->getKey(),
                'product_id' => $this->product->getKey(),
                'required_quantity' => 15,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductItemsHasRequiredQuantityInsteadOfQuantity(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->product->items()->attach($this->items->get(0)->getKey(), ['required_quantity' => 5]);
        $this->product->items()->attach($this->items->get(1)->getKey(), ['required_quantity' => 15]);

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices_base' => $this->prices,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment(['required_quantity' => 5])
            ->assertJsonFragment(['required_quantity' => 15])
            ->assertJsonMissing(['quantity']);
    }
}
