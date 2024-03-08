<?php

namespace Tests\Feature\Products;

use App\Enums\SchemaType;
use App\Models\Price;
use App\Models\Product;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Metadata\Enums\MetadataType;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductTestCase extends TestCase
{
    protected Product $product;
    protected array $expected_short;
    protected Currency $currency;

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
        $schemaCrudService = App::make(SchemaCrudService::class);

        $productPrices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        /** @var AvailabilityServiceContract $availabilityService */
        $availabilityService = App::make(AvailabilityServiceContract::class);

        $this->product = $productService->create(
            FakeDto::productCreateDto([
                'shipping_digital' => false,
                'public' => true,
                'created_at' => now()->subHours(5),
                'prices_base' => $productPrices,
            ])
        );

        $schema = $schemaCrudService->store(
            FakeDto::schemaDto([
                'name' => 'Rozmiar',
                'type' => SchemaType::SELECT,
                'prices' => [PriceDto::from(Money::of(0, $this->currency->value))],
                'required' => true,
            ])
        );
        $this->product->schemas()->attach($schema->getKey());

        $this->travel(5)->hours();

        $l = $schema->options()->create([
            'name' => 'L',
            'order' => 2,
        ]);
        $l->prices()->createMany(
            Price::factory(['value' => 0])->prepareForCreateMany(),
        );

        $l->items()->create([
            'name' => 'Koszulka L',
            'sku' => 'K001/L',
        ]);

        $this->travelBack();

        $xl = $schema->options()->create([
            'name' => 'XL',
            'order' => 1,
        ]);
        $xl->prices()->createMany(
            Price::factory(['value' => 0])->prepareForCreateMany(),
        );

        $item = $xl->items()->create([
            'name' => 'Koszulka XL',
            'sku' => 'K001/XL',
        ]);

        $item->deposits()->create([
            'quantity' => 10,
        ]);

        $this->product->metadata()->create([
            'name' => 'testMetadata',
            'value' => 'value metadata',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this->product->attributes()->attach($attribute->getKey());
        $this->product->attributes->first()->product_attribute_pivot->options()->attach($option->getKey());

        $availabilityService->calculateItemAvailability($item);

        // Expected short response
        $this->expected_short = [
            'id' => $this->product->getKey(),
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'visible' => $this->product->public,
            'public' => (bool) $this->product->public,
            'available' => true,
            'cover' => null,
        ];
    }
}
