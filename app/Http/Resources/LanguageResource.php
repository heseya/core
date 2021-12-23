<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LanguageResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'iso' => $this->iso,
            'name' => $this->name,
            'default' => $this->default,
            'hidden' => $this->hidden,
        ];
    }
}
