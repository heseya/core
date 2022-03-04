<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class StatusResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'color' => $this->color,
            'cancel' => $this->cancel,
            'description' => $this->description,
            'hidden' => $this->hidden,
            'no_notifications' => $this->no_notifications,
        ];
    }

    public function view(Request $request): array
    {
        return $this->metadataResource('statuses');
    }
}
