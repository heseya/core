<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

class OptionResource extends Resource
{
    use GetAllTranslations;

    public function base(Request $request): array
    {
        $data = [
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->price,
            'disabled' => $this->disabled,
            'available' => $this->available,
            'items' => ItemResource::collection($this->items),
        ];

        return array_merge($data, array_key_exists('translations', $request->toArray()) ? $this->getAllTranslations() : []);
    }
}
