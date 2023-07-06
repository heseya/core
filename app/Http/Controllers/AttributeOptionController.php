<?php

namespace App\Http\Controllers;

use App\DTO\ReorderDto;
use App\Dtos\AttributeOptionDto;
use App\Http\Requests\AttributeOptionIndexRequest;
use App\Http\Requests\AttributeOptionRequest;
use App\Http\Resources\AttributeOptionResource;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Services\Contracts\AttributeOptionServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class AttributeOptionController extends Controller
{
    public function __construct(
        private readonly AttributeOptionServiceContract $attributeOptionService,
        private readonly ReorderServiceContract $reorderService,
    ) {
    }

    public function index(AttributeOptionIndexRequest $request, Attribute $attribute): JsonResource
    {
        $query = $attribute
            ->options()
            ->searchByCriteria($request->validated())
            ->orderBy('order')
            ->with(['metadata', 'metadataPrivate']);

        return AttributeOptionResource::collection(
            $query->paginate(Config::get('pagination.per_page'))
        );
    }

    public function store(Attribute $attribute, AttributeOptionRequest $request): JsonResource
    {
        $attributeOption = $this->attributeOptionService->create(
            $attribute->getKey(),
            AttributeOptionDto::instantiateFromRequest($request)
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
            AttributeOptionDto::instantiateFromRequest($request)
        );

        return AttributeOptionResource::make($attributeOption);
    }

    public function destroy(Attribute $attribute, AttributeOption $option): HttpResponse
    {
        $this->attributeOptionService->delete($option);

        return Response::noContent();
    }

    public function reorder(ReorderDto $dto): HttpResponse
    {
        $this->reorderService->reorderAndSave(AttributeOption::class, $dto);

        return Response::noContent();
    }
}
