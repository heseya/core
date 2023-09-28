<?php

namespace Database\Factories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    protected bool $withPrice = true;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(mt_rand(2, 3));

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . mt_rand(1, 99999),
            'description_html' => '<p>' . $this->faker->sentence(10) . '</p>',
            'description_short' => $this->faker->sentence(10),
            'published' => [App::getLocale()],
        ];
    }

    public function withPrice(bool $with = true): self
    {
        $this->withPrice = $with;
        return $this;
    }

    public function withoutPrice(): self
    {
        return $this->withPrice(false);
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Product $product): void {
            if ($this->withPrice) {
                /** @var ProductRepositoryContract $productRepository */
                $productRepository = App::make(ProductRepositoryContract::class);

                $price = PriceDto::from(Money::of(
                    round(mt_rand(500, 6000), -2),
                    Currency::DEFAULT->value,
                ))->withSalesChannel(app(SalesChannelRepository::class)->getDefault());

                $productRepository->setProductPrices($product->getKey(), [
                    ProductPriceType::PRICE_BASE->value => [$price],
                    ProductPriceType::PRICE_MIN_INITIAL->value => [$price],
                    ProductPriceType::PRICE_MAX_INITIAL->value => [$price],
                ]);
            }
        });
    }
}
