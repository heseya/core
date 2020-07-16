<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StatusResource extends Resource
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
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'description' => $this->description,
        ];
    }
}
