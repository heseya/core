<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Observers;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;

final readonly class AttributeOptionObserver
{
    public function created(AttributeOption $attributeOption): void
    {
        $this->updateMinMax($attributeOption);
    }

    public function updated(AttributeOption $attributeOption): void
    {
        $this->updateMinMax($attributeOption);
    }

    public function deleted(AttributeOption $attributeOption): void
    {
        $this->updateMinMax($attributeOption);
    }

    public function restored(AttributeOption $attributeOption): void
    {
        $this->updateMinMax($attributeOption);
    }

    private function updateMinMax(AttributeOption $attributeOption): void
    {
        /** @var Attribute $attribute */
        $attribute = $attributeOption->attribute()->first();

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
