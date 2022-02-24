<?php

namespace App\Http\Resources;

use App\Enums\AttributeType;
use Illuminate\Http\Request;

class AttributeResource extends Resource
{
    public function base(Request $request): array
    {
        switch ($this->type) {
            case AttributeType::NUMBER:
                $min = $this->min_number;
                $max = $this->max_number;
                break;

            case AttributeType::DATE:
                $min = $this->min_date;
                $max = $this->max_date;
                break;

            default:
                $min = null;
                $max = null;
        }

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
