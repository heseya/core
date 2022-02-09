<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

class SchemaResource extends Resource
{
    use GetAllTranslations;

    public function base(Request $request): array
    {
        $data =  [
            'id' => $this->getKey(),
            'type' => $this->type->key,
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
            'used_schemas' => $this->usedSchemas->map(fn ($schema) => $schema->getKey()),
        ];

        return array_merge($data, array_key_exists('translations', $request->toArray()) ? $this->getAllTranslations() : []);
    }

    public function view(Request $request): array
    {
        return [
            'products' => ProductResource::collection($this->products),
        ];
    }
}
