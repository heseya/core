<?php

namespace App\Observers;

use App\Models\AttributeOption;
use App\Services\Contracts\AttributeServiceContract;

class AttributeOptionObserver
{
    public function __construct(private AttributeServiceContract $attributeService)
    {
    }

    /**
     * Handle the AttributeOption "created" event.
     *
     * @param AttributeOption $attributeOption
     *
     * @return void
     */
    public function created(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax(
            $attributeOption->attribute,
            $attributeOption->value_number,
            $attributeOption->value_date
        );
    }

    /**
     * Handle the AttributeOption "updated" event.
     *
     * @param AttributeOption $attributeOption
     *
     * @return void
     */
    public function updated(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax(
            $attributeOption->attribute,
            $attributeOption->value_number,
            $attributeOption->value_date
        );
    }

    /**
     * Handle the AttributeOption "deleted" event.
     *
     * @param AttributeOption  $attributeOption
     *
     * @return void
     */
    public function deleted(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax(
            $attributeOption->attribute,
            $attributeOption->value_number,
            $attributeOption->value_date
        );
    }

    /**
     * Handle the AttributeOption "restored" event.
     *
     * @param AttributeOption  $attributeOption
     *
     * @return void
     */
    public function restored(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax(
            $attributeOption->attribute,
            $attributeOption->value_number,
            $attributeOption->value_date
        );
    }
}
