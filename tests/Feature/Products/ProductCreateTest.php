<?php

namespace Tests\Feature\Products;

use App\Dtos\ProductCreateDto;
use App\Enums\Currency;
use App\Models\Page;
use App\Services\Contracts\ProductServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ProductCreateTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCreateDescriptions(string $user): void
    {
        $page = Page::factory()->create();

        $prices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->{$user}->givePermissionTo('products.add');
        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'slug',
                'prices_base' => $prices,
                'public' => true,
                'shipping_digital' => false,
                'descriptions' => [$page->getKey()],
            ])
            ->assertCreated()
            ->assertJsonPath('data.descriptions.0.id', $page->getKey());

        $this->assertDatabaseHas('product_page', [
            'product_id' => $response->json('data.id'),
            'page_id' => $page->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public function testUpdateDescriptions(string $user): void
    {
        /** @var ProductServiceContract $productService */
        $productService = App::make(ProductServiceContract::class);
        $product = $productService->create(ProductCreateDto::fake());
        $page = Page::factory()->create();

        $this->{$user}->givePermissionTo('products.edit');
        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'descriptions' => [$page->getKey()],
            ])
            ->assertOk()
            ->assertJsonPath('data.descriptions.0.id', $page->getKey());

        $this->assertDatabaseHas('product_page', [
            'product_id' => $response->json('data.id'),
            'page_id' => $page->getKey(),
        ]);
    }
}
