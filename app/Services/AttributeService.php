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

readonly class AttributeService implements AttributeServiceContract
{
    public function __construct(
        private AttributeOptionServiceContract $attributeOptionService,
        private MetadataServiceContract $metadataService,
    ) {}

    public function create(AttributeDto $dto): Attribute
    {
        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($dto->toArray());

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($attribute, $dto->getMetadata());
        }

        return $attribute;
    }

    public function update(Attribute $attribute, AttributeDto $dto): Attribute
    {
        $attribute->update($dto->toArray());

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
        $product->attributes()->get()->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->sync($data[$attribute->getKey()]),
        );
    }

    public function updateMinMax(Attribute $attribute): void
    {
        $attribute->refresh();

        if ($attribute->type === AttributeType::NUMBER) {
            $attribute->min_number = $attribute->options->min('value_number');
            $attribute->max_number = $attribute->options->max('value_number');
            $attribute->save();
        } elseif ($attribute->type === AttributeType::DATE) {
            $attribute->min_date = $attribute->options->min('value_date');
            $attribute->max_date = $attribute->options->max('value_date');
            $attribute->save();
        }
    }
}
