<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Controllers;

use App\DTO\ReorderDto;
use App\Exceptions\ClientException;
use App\Http\Controllers\Controller;
use App\Services\Contracts\ReorderServiceContract;
use App\Traits\GetPublishedLanguageFilter;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeIndexDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Dtos\FiltersDto;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Resources\AttributeResource;
use Domain\ProductAttribute\Services\AttributeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

final class AttributeController extends Controller
{
    use GetPublishedLanguageFilter;

    public function __construct(
        private readonly AttributeService $attributeService,
        private readonly ReorderServiceContract $reorderService,
    ) {}

    public function index(AttributeIndexDto $dto): JsonResource
    {
        return AttributeResource::collection(
            Attribute::searchByCriteria($dto->toArray() + $this->getPublishedLanguageFilter('attributes'))
                ->with(['metadata', 'metadataPrivate'])
                ->orderBy('order')
                ->paginate(Config::get('pagination.per_page')),
        );
    }

    public function filters(FiltersDto $dto): JsonResource
    {
        return AttributeResource::collection(
            Attribute::query()
                ->whereHas(
                    'productSets',
                    fn ($query) => $query->whereIn('product_set_id', $dto->sets),
                )
                ->orWhere('global', '=', true)
                ->with(['metadata', 'metadataPrivate'])
                ->orderBy('order')
                ->get(),
        );
    }

    public function show(
        Request $request,
        Attribute $attribute
    ): JsonResource {
        if (!$attribute->exists) {
            $attribute->refresh();
        }
        if (!$attribute->exists) {
            throw (new ModelNotFoundException())->setModel(Attribute::class, (string) $request->segment(2));
        }

        return AttributeResource::make($attribute);
    }

    public function store(AttributeCreateDto $dto): JsonResource
    {
        return AttributeResource::make(
            $this->attributeService->create($dto),
        );
    }

    /**
     * @throws ClientException
     */
    public function update(string $id, AttributeUpdateDto $dto): JsonResource
    {
        $this->attributeService->update($id, $dto);

        return AttributeResource::make(
            $this->attributeService->show($id),
        );
    }

    public function destroy(Attribute $attribute): HttpResponse
    {
        $this->attributeService->delete($attribute->getKey());

        return Response::noContent();
    }

    public function reorder(ReorderDto $dto): HttpResponse
    {
        $this->reorderService->reorderAndSave(Attribute::class, $dto);

        return Response::noContent();
    }
}
