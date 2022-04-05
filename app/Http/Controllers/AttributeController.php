<?php

namespace App\Http\Controllers;

use App\Dtos\AttributeDto;
use App\Http\Requests\AttributeIndexRequest;
use App\Http\Requests\AttributeStoreRequest;
use App\Http\Requests\AttributeUpdateRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Services\Contracts\AttributeServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class AttributeController extends Controller
{
    public function __construct(private AttributeServiceContract $attributeService)
    {
    }

    public function index(AttributeIndexRequest $request): JsonResource
    {
        $query = Attribute::searchByCriteria($request->validated())
            ->with(['options', 'metadata', 'metadataPrivate']);

        return AttributeResource::collection(
            $query->paginate(Config::get('pagination.per_page'))
        );
    }

    public function show(Attribute $attribute): JsonResource
    {
        $attribute->load('options');

        return AttributeResource::make($attribute);
    }

    public function store(AttributeStoreRequest $request): JsonResource
    {
        $attribute = $this->attributeService->create(
            AttributeDto::fromFormRequest($request)
        );

        return AttributeResource::make($attribute);
    }

    public function update(Attribute $attribute, AttributeUpdateRequest $request): JsonResource
    {
        $attribute = $this->attributeService->update(
            $attribute,
            AttributeDto::fromFormRequest($request)
        );

        return AttributeResource::make($attribute);
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $this->attributeService->delete($attribute);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
