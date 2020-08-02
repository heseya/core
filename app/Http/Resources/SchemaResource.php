<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SchemaResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'type' => $this->type,
            'required' => $this->required,
            'schema_items' => SchemaItemResource::collection($this->schemaItems),
        ];
    }
}
