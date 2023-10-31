<?php

namespace App\Http\Resources;

use App\Traits\ModifyLangFallback;

class ProductSchemaOptionResource extends OptionResource
{
    use ModifyLangFallback;

    public function toArray($request): array
    {
        $previousSettings = $this->getCurrentLangFallbackSettings();
        $this->setAnyLangFallback();
        $result = parent::toArray($request);
        $this->setLangFallbackSettings(...$previousSettings);

        return $result;
    }
}
