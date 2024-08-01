<?php

declare(strict_types=1);

namespace Domain\Organization\Resources;

use App\Http\Resources\ConsentResource;
use Illuminate\Http\Request;

final class ConsentOrganizationResource extends ConsentResource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return parent::base($request) + ['value' => $this->resource->pivot->value];
    }
}
