<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class OrderDocumentResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'name' => $this->resource->name,
        ];
    }
}
