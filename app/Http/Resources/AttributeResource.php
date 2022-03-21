<?php

namespace App\Http\Resources;

use App\Enums\AttributeType;
use Illuminate\Http\Request;

class AttributeResource extends Resource
{
    public function base(Request $request): array
    {
        [$min, $max] = match ($this->type->value) {
            AttributeType::NUMBER => [$this->min_number, $this->max_number],
            AttributeType::DATE => [$this->min_date, $this->max_date],
            default => [null, null],
        };

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'min' => $min,
            'max' => $max,
            'type' => $this->type,
            'global' => $this->global,
            'sortable' => $this->sortable,
            'options' => AttributeOptionResource::collection($this->options),
        ];
    }
}
