<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Resources;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class PaymentMethodResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'icon' => $this->resource->icon,
            'alias' => $this->resource->alias,
            'public' => $this->resource->public,
            'type' => $this->resource->type,
            'creates_default_payment' => $this->resource->creates_default_payment,
        ];
    }
}
