<?php

namespace App\Services;

use App\Dtos\AttributeDto;
use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Product;
use App\Services\Contracts\AttributeOptionServiceContract;
use App\Services\Contracts\AttributeServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Support\Arr;

class AttributeService implements AttributeServiceContract
{
    public function __construct(
        private AttributeOptionServiceContract $attributeOptionService,
        private MetadataServiceContract $metadataService,
    ) {
    }

    public function create(AttributeDto $dto): Attribute
    {
        $attribute = Attribute::create($dto->toArray());
        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($attribute, $dto->getMetadata());
        }

        $this->processAttributeOptions($attribute, $dto);

        return $attribute;
    }

    public function update(Attribute $attribute, AttributeDto $dto): Attribute
    {
        $attribute->update($dto->toArray());

        $this->processAttributeOptions($attribute, $dto);

        return $attribute;
    }

    public function delete(Attribute $attribute): void
    {
        $this->attributeOptionService->deleteAll($attribute->getKey());

        $attribute->delete();
    }

    public function sync(Product $product, array $data): void
    {
        $attributes = Arr::divide($data)[0];

        $product->attributes()->sync($attributes);
        $product->attributes->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->sync($data[$attribute->getKey()])
        );
    }

    public function updateMinMax(Attribute $attribute): void
    {
        $attribute->refresh();

        if ($attribute->type->value === AttributeType::NUMBER) {
            $attribute->setAttribute('min_number', $attribute->options->min('value_number'));
            $attribute->setAttribute('max_number', $attribute->options->max('value_number'));
        }

        if ($attribute->type->value === AttributeType::DATE) {
            $attribute->setAttribute('min_date', $attribute->options->min('value_date'));
            $attribute->setAttribute('max_date', $attribute->options->max('value_date'));
        }

        $attribute->update();
    }

    protected function processAttributeOptions(Attribute &$attribute, AttributeDto $dto): Attribute
    {
        $attribute->options
            ->whereNotIn('id', array_map(fn ($option) => $option->getId(), $dto->getOptions()))
            ->each(
                fn ($missingOption) => $this->attributeOptionService->delete($missingOption)
            );

        foreach ($dto->getOptions() as $option) {
            $attributeOption = $this->attributeOptionService->updateOrCreate($attribute->getKey(), $option);

            if (!($option->getMetadata() instanceof Missing)) {
                $this->metadataService->sync($attributeOption, $option->getMetadata());
            }
        }

        return $attribute->refresh();
    }
}
