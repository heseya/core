<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SettingResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'public' => $this->public,
            'permanent' => $this->permanent,
        ];
    }
}
