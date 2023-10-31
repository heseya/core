<?php

namespace Feature\Products;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportPricesTest extends TestCase
{
    use WithFaker;

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductPriceFromCsvFile(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'sku',
            'slug' => 'sku',
        ]);

        $sku = $this->faker->numberBetween(100);
        /** @var AttributeOption $option */
        $option = AttributeOption::factory()
            ->for($attribute)
            ->create([
                'name' => $sku,
                'index' => 0,
            ]);

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->attributes()->attach($attribute->getKey());
        $product->attributes()->get()->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->attach($option->getKey()),
        );

        $emptyRow = ',,,,,,,,,,';
        $header = 'Rodzaj podłoża,Indeks,Symbol,Nazwa,Gr / Grubość,Szer.             mm,Dł. m,Opak.,Cena netto za   1 rolkę,EndUser netto,EndUser brutto';
        $subtitle = 'Papiery niepowlekane,,,,,,,,1 rolka,,';
        $productRow = ",{$sku},,Standard Papier,80g/m2,610 mm,50 m,3 rollki,\"98,10 zł\",,";
        $content = implode("\n", [$emptyRow, $header, $emptyRow, $subtitle, $productRow]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/products/import-prices', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $content),
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price' => 98.10,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductPriceFromXMLFile(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'ean',
            'slug' => 'ean',
        ]);

        /** @var AttributeOption $option */
        $option = AttributeOption::factory()
            ->for($attribute)
            ->create([
                'name' => '5901741418343',
                'index' => 0,
            ]);

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->attributes()->attach($attribute->getKey());
        $product->attributes()->get()->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->attach($option->getKey()),
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>
                    <oferta>
                        <naglowek>
                            <data>18.09.2023 13:43:18</data>
                        </naglowek>
                        <Produkt>
                            <cenabrutto>6.15</cenabrutto>
                            <EAN>5901741418343</EAN>
                        </Produkt>
                    </oferta>';

        $this
            ->actingAs($this->{$user})
            ->postJson('/products/import-prices', [
                'file' => UploadedFile::fake()->createWithContent('products.xml', $content),
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price' => 6.15,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShouldNotUpdateProductPriceFromXMLFileWhenProductNotExists(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create([
            'name' => 'ean',
            'slug' => 'ean',
        ]);

        /** @var AttributeOption $option */
        $option = AttributeOption::factory()
            ->for($attribute)
            ->create([
                'name' => '590',
                'index' => 0,
            ]);

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->attributes()->attach($attribute->getKey());
        $product->attributes()->get()->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->attach($option->getKey()),
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>
                    <oferta>
                        <Produkt>
                            <cenabrutto>6.15</cenabrutto>
                            <EAN>5901741418343</EAN>
                        </Produkt>
                    </oferta>';

        $this
            ->actingAs($this->{$user})
            ->postJson('/products/import-prices', [
                'file' => UploadedFile::fake()->createWithContent('products.xml', $content),
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price' => $product->price,
        ]);
    }
}
