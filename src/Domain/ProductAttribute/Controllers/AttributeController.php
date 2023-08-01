<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Controllers;

use App\DTO\ReorderDto;
use App\Exceptions\ServerException;
use App\Http\Controllers\Controller;
use App\Services\Contracts\ReorderServiceContract;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeIndexDto;
use Domain\ProductAttribute\Dtos\AttributeResponseDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Dtos\FiltersDto;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Services\AttributeService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\PaginatedDataCollection;

final class AttributeController extends Controller
{
    public function __construct(
        private readonly AttributeService $attributeService,
        private readonly ReorderServiceContract $reorderService,
    ) {}

    /**
     * @param AttributeIndexDto $dto
     *
     * @return PaginatedDataCollection<int, AttributeResponseDto>
     * @throws ServerException
     */
    public function index(AttributeIndexDto $dto): PaginatedDataCollection
    {
        return $this->attributeService->index($dto);
    }

    /**
     * @param FiltersDto $dto
     *
     * @return DataCollection<int, AttributeResponseDto>
     */
    public function filters(FiltersDto $dto): DataCollection
    {
        return $this->attributeService->filters($dto);
    }

    public function show(string $id): AttributeResponseDto
    {
        return $this->attributeService->show($id);
    }

    public function store(AttributeCreateDto $dto): AttributeResponseDto
    {
        return $this->attributeService->create($dto);
    }

    public function update(string $id, AttributeUpdateDto $dto): AttributeResponseDto
    {
        return $this->attributeService->update($id, $dto);
    }

    public function destroy(string $id): HttpResponse
    {
        $this->attributeService->delete($id);

        return Response::noContent();
    }

    public function reorder(ReorderDto $dto): HttpResponse
    {
        $this->reorderService->reorderAndSave(Attribute::class, $dto);

        return Response::noContent();
    }
}
