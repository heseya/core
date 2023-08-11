<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Controllers;

use App\DTO\ReorderDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttributeOptionIndexRequest;
use App\Services\Contracts\ReorderServiceContract;
use Domain\ProductAttribute\Dtos\AttributeOptionDto;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Resources\AttributeOptionResource;
use Domain\ProductAttribute\Services\AttributeOptionService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

final class AttributeOptionController extends Controller
{
    public function __construct(
        private readonly AttributeOptionService $attributeOptionService,
        private readonly ReorderServiceContract $reorderService,
    ) {}

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

    public function store(Attribute $attribute, AttributeOptionDto $dto): JsonResource
    {
        $attributeOption = $this->attributeOptionService->create($dto);

        return AttributeOptionResource::make($attributeOption);
    }

    public function update(Attribute $attribute, AttributeOption $option, AttributeOptionDto $dto): JsonResource
    {
        if (!$dto->id) {
            $dto->id = $option->getKey();
        }

        $attributeOption = $this->attributeOptionService->updateOrCreate($dto);

        return AttributeOptionResource::make($attributeOption);
    }

    /**
     * @throws ClientException
     */
    public function destroy(Attribute $attribute, AttributeOption $option): HttpResponse
    {
        if (!$attribute->options()->where('id', '=', $option->getKey())->exists()) {
            throw new ClientException(Exceptions::CLIENT_OPTION_NOT_RELATED_TO_ATTRIBUTE);
        }

        $this->attributeOptionService->delete($option);

        return Response::noContent();
    }

    /**
     * @throws ClientException
     */
    public function reorder(Attribute $attribute, ReorderDto $dto): HttpResponse
    {
        if ($attribute->options()->whereIn('id', $dto->ids)->count() !== count($dto->ids)) {
            throw new ClientException(Exceptions::CLIENT_OPTION_NOT_RELATED_TO_ATTRIBUTE);
        }

        $this->reorderService->reorderAndSave(AttributeOption::class, $dto);

        return Response::noContent();
    }
}
