<?php

namespace App\Observers;

use App\Models\AttributeOption;
use App\Services\Contracts\AttributeServiceContract;

final readonly class AttributeOptionObserver
{
    public function __construct(
        private AttributeServiceContract $attributeService,
    ) {}

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
        if ($attributeOption->attribute !== null) {
            $this->attributeService->updateMinMax($attributeOption->attribute);
        }
    }
}
