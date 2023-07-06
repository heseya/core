<?php

namespace App\Http\Controllers;

use App\DTO\ReorderDto;
use App\Dtos\AttributeDto;
use App\Http\Requests\AttributeIndexRequest;
use App\Http\Requests\AttributeStoreRequest;
use App\Http\Requests\AttributeUpdateRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Services\Contracts\AttributeServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class AttributeController extends Controller
{
    public function __construct(
        private readonly AttributeServiceContract $attributeService,
        private readonly ReorderServiceContract $reorderService,
    ) {
    }

    public function index(AttributeIndexRequest $request): JsonResource
    {
        $query = Attribute::searchByCriteria($request->validated())
            ->orderBy('order')
            ->with(['metadata', 'metadataPrivate']);

        return AttributeResource::collection(
            $query->paginate(Config::get('pagination.per_page'))
        );
    }

    public function show(Attribute $attribute): JsonResource
    {
        return AttributeResource::make($attribute);
    }

    public function store(AttributeStoreRequest $request): JsonResource
    {
        $attribute = $this->attributeService->create(
            AttributeDto::instantiateFromRequest($request)
        );

        return AttributeResource::make($attribute);
    }

    public function update(Attribute $attribute, AttributeUpdateRequest $request): JsonResource
    {
        $attribute = $this->attributeService->update(
            $attribute,
            AttributeDto::instantiateFromRequest($request)
        );

        return AttributeResource::make($attribute);
    }

    public function destroy(Attribute $attribute): HttpResponse
    {
        $this->attributeService->delete($attribute);

        return Response::noContent();
    }

    public function reorder(ReorderDto $dto): HttpResponse
    {
        $this->reorderService->reorderAndSave(Attribute::class, $dto);

        return Response::noContent();
    }
}
