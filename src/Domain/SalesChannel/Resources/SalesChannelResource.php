<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Resources;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class SalesChannelResource extends Resource
{
    /**
     * @return array<string, string[]>
     */
    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
        ];
    }
}
