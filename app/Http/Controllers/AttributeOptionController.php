<?php

namespace App\Http\Controllers;

use App\Dtos\AttributeOptionDto;
use App\Http\Requests\AttributeOptionRequest;
use App\Http\Resources\AttributeOptionResource;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Services\Contracts\AttributeOptionServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

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

    public function update(Attribute $attribute, AttributeOption $option, AttributeOptionRequest $request): JsonResource
    {
        if (!$request->has('id')) {
            $request->merge(['id' => $option->getKey()]);
        }

        $attributeOption = $this->attributeOptionService->updateOrCreate(
            $attribute->getKey(),
            AttributeOptionDto::fromFormRequest($request)
        );

        return AttributeOptionResource::make($attributeOption);
    }

    public function destroy(Attribute $attribute, AttributeOption $option): JsonResponse
    {
        $this->attributeOptionService->delete($option);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
