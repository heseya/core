<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\Schema;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductTest extends TestCase
{
    private Product $product;
    private Currency $currency;
    private SchemaCrudService $schemaCrudService;

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::DEFAULT;

        $productService = App::make(ProductService::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);

        $productPrices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->product = $productService->create(
            FakeDto::productCreateDto([
                'shipping_digital' => false,
                'public' => true,
                'created_at' => now()->subHours(5),
                'prices_base' => $productPrices,
            ])
        );
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemaDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('schemas.remove');

        Schema::query()->delete();
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'test schema',
            ])
        );

        $this->product->schemas()->save($schema);
        $this->product->update(['has_schemas' => true]);

        $this->actingAs($this->{$user})->json('delete', 'schemas/id:' . $schema->getKey());

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemaAdded(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Schema::query()->delete();
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'test schema',
            ])
        );

        $this->actingAs($this->{$user})->json('patch', 'products/id:' . $this->product->getKey(), [
            'schemas' => [
                $schema->getKey(),
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductHasSchemaOnSchemasRemovedFromProduct(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Schema::query()->delete();
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'test schema',
            ])
        );

        $this->product->schemas()->save($schema);

        $this->product->update(['has_schemas' => true]);

        $this->actingAs($this->{$user})->json('patch', 'products/id:' . $this->product->getKey(), [
            'schemas' => [],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'has_schemas' => false,
        ]);
    }
}
