<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttributeOptionResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'value_text' => $this->value_text,
            'value' => $this->value,
            'attribute_id' => $this->attribute_id,
        ];
    }
}
