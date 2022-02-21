<?php

namespace App\Http\Controllers;

use App\Dtos\AttributeOptionDto;
use App\Http\Requests\AttributeOptionRequest;
use App\Http\Resources\AttributeOptionResource;
use App\Models\Attribute;
use App\Services\Contracts\AttributeOptionServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeOptionController extends Controller
{
    public function __construct(private AttributeOptionServiceContract $attributeOptionService)
    {
    }

    public function store(Attribute $attribute, AttributeOptionRequest $request): JsonResource
    {
        $attributeOption = $this->attributeOptionService->create(
            $attribute->getKey(),
            AttributeOptionDto::fromFormRequest($request)
        );

        return AttributeOptionResource::make($attributeOption);
    }
}
