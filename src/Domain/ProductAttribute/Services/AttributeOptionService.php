<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use App\Models\Item;
use App\Models\Product;
use App\Services\Contracts\MetadataServiceContract;
use Domain\ProductAttribute\Dtos\AttributeOptionDto;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Repositories\AttributeOptionRepository;
use Spatie\LaravelData\Optional;

final readonly class AttributeOptionService
{
    public function __construct(
        private MetadataServiceContract $metadataService,
        private AttributeOptionRepository $attributeOptionRepository,
    ) {}

    public function create(AttributeOptionDto $dto): AttributeOption
    {
        $attributeOption = $this->attributeOptionRepository->create($dto);

        if (!($dto->metadata_computed instanceof Optional)) {
            $this->metadataService->sync($attributeOption, $dto->metadata_computed);
        }

        return $attributeOption;
    }

    public function updateOrCreate(AttributeOptionDto $dto): AttributeOption
    {
        if ($dto->id !== null && !$dto->id instanceof Optional) {
            $attributeOption = $this->attributeOptionRepository->update($dto->id, $dto);
        } else {
            $attributeOption = $this->create($dto);
        }

        return $attributeOption;
    }

    public function delete(AttributeOption $attributeOption): void
    {
        $attributeOption->delete();
    }

    public function deleteAll(string $attributeId): void
    {
        AttributeOption::query()
            ->where('attribute_id', '=', $attributeId)
            ->delete();
    }

    public function importSku(Attribute $attribute, Product $product, string $locale): void
    {
        $item = $product->items->first(fn (Item $item) => $item->sku !== null);
        if ($item) {
            $sku = $item->sku;
            $option = $attribute->options->first(fn (AttributeOption $option) => in_array($sku, $option->getTranslations('name'), true));
            if (!$option) {
                $option = $this->create(AttributeOptionDto::from([
                    'attribute_id' => $attribute->getKey(),
                    'translations' => [
                        $locale => [
                            'name' => $sku,
                        ],
                    ],
                ]));
            }
            $product->attributes()->attach($attribute);
            $product->refresh();
            $product->attributes
                ->first(fn (Attribute $productAttribute) => $productAttribute->getKey() === $attribute->getKey())
                ?->product_attribute_pivot?->options()->sync([$option->getKey()]);
        }
    }
}
