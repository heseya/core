<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use App\Models\Item;
use App\Models\Product;
use App\Services\Contracts\MetadataServiceContract;
use Domain\Language\LanguageService;
use Domain\ProductAttribute\Dtos\AttributeOptionDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Repositories\AttributeOptionRepository;
use Illuminate\Support\Facades\App;
use Spatie\LaravelData\Optional;

final readonly class AttributeOptionService
{
    public function __construct(
        private MetadataServiceContract $metadataService,
        private AttributeOptionRepository $attributeOptionRepository,
    ) {}

    public function create(AttributeOptionDto $dto, bool $existingId = false): AttributeOption
    {
        $attributeOption = $this->attributeOptionRepository->firstOrCreate($dto, $existingId);

        if (!($dto->metadata_computed instanceof Optional)) {
            $this->metadataService->sync($attributeOption, $dto->metadata_computed);
        }

        return $attributeOption;
    }

    public function updateOrCreate(AttributeOptionDto $dto, Attribute $attribute, AttributeOption $option): AttributeOption
    {
        if ($this->checkIfValueChanged($option, $dto, $attribute->type)) {
            $attributeOption = $this->create($dto, true);
        } else {
            $attributeOption = $this->attributeOptionRepository->update($option, $dto);
        }

        return $attributeOption;
    }

    public function delete(AttributeOption $attributeOption): void
    {
        $order = $attributeOption->order;
        $attributeOption->delete();

        if ($attributeOption->attribute) {
            foreach ($attributeOption->attribute->options()->where('order', '>', $order)->orderBy('order')->cursor() as $option) {
                $option->update(['order' => $order++]);
            }
        }
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

    private function checkIfValueChanged(AttributeOption $option, AttributeOptionDto $dto, AttributeType $type): bool
    {
        return match ($type) {
            AttributeType::NUMBER => $option->value_number !== $dto->value_number,
            AttributeType::DATE => $option->value_date !== $dto->value_date,
            AttributeType::SINGLE_OPTION, AttributeType::MULTI_CHOICE_OPTION => $this->checkIfNameChanged($option->name, $dto->translations),
        };
    }

    /**
     * @param array<string, array<string, string>>|null $translations
     */
    private function checkIfNameChanged(string $name, ?array $translations): bool
    {
        /** @var string $defaultLanguage */
        $defaultLanguage = App::make(LanguageService::class)->defaultLanguage()->getKey();

        if ($translations && array_key_exists($defaultLanguage, $translations)) {
            return isset($translations[$defaultLanguage]['name']) && $name !== $translations[$defaultLanguage]['name'];
        }

        return false;
    }
}
