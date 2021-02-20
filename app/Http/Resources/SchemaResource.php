<?php

namespace App\Http\Resources;

use App\Models\Schema;
use Illuminate\Http\Request;

class SchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'type' => Schema::TYPES[$this->type],
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'hidden' => $this->hidden,
            'required' => $this->required,
            'available' => $this->available,
            'max' => $this->max,
            'min' => $this->min,
            'step' => $this->step,
            'default' => $this->default,
            'pattern' => $this->pattern,
            'validation' => $this->validation,
            'options' => OptionResource::collection($this->options),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'products' => ProductResource::collection($this->products),
        ];
    }
}
