<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttributeOptionResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'index' => $this->index,
            'value_number' => $this->value_number,
            'value_date' => $this->value_date,
            'attribute_id' => $this->attribute_id,
        ];
    }
}
