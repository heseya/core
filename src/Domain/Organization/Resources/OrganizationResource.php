<?php

declare(strict_types=1);

namespace Domain\Organization\Resources;

use App\Http\Resources\AddressResource;
use App\Http\Resources\Resource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

final class OrganizationResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'status' => $this->resource->status,
            'phone' => $this->resource->phone,
            'email' => $this->resource->email,
            'address' => AddressResource::make($this->resource->address),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function view(Request $request): array
    {
        return [
            'assistants' => UserResource::collection($this->resource->assistants),
            'users' => UserResource::collection($this->resource->users),
        ];
    }
}
