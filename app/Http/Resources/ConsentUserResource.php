<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

final class ConsentUserResource extends ConsentResource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return parent::base($request) + ['value' => $this->resource->pivot->value];
    }
}
