<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

class StatusResource extends Resource
{
    use GetAllTranslations;

    public function base(Request $request): array
    {
        $data = [
            'id' => $this->getKey(),
            'name' => $this->name,
            'color' => $this->color,
            'cancel' => $this->cancel,
            'description' => $this->description,
            'hidden' => $this->hidden,
            'no_notifications' => $this->no_notifications,
        ];

        return array_merge(
            $data,
            $request->has('translations') ? $this->getAllTranslations() : []
        );
    }
}
