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
     */
    public function created(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax($attributeOption->attribute);
    }

    /**
     * Handle the AttributeOption "updated" event.
     *
     * @param AttributeOption $attributeOption
     */
    public function updated(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax($attributeOption->attribute);
    }

    /**
     * Handle the AttributeOption "deleted" event.
     *
     * @param AttributeOption  $attributeOption
     */
    public function deleted(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax($attributeOption->attribute);
    }

    /**
     * Handle the AttributeOption "restored" event.
     *
     * @param AttributeOption  $attributeOption
     */
    public function restored(AttributeOption $attributeOption): void
    {
        $this->attributeService->updateMinMax($attributeOption->attribute);
    }
}
