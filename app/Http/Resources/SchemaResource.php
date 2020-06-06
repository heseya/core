<?php

namespace App\Http\Resources;

class SchemaResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function base($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'required' => $this->required,
            'schema_items' => SchemaItemResource::collection($this->schemaItems),
        ];
    }
}
