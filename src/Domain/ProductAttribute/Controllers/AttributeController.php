<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Controllers;

use App\DTO\ReorderDto;
use App\Http\Controllers\Controller;
use App\Services\Contracts\ReorderServiceContract;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeIndexDto;
use Domain\ProductAttribute\Dtos\AttributeResponseDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Services\AttributeService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class AttributeController extends Controller
{
    public function __construct(
        private readonly AttributeService $attributeService,
        private readonly ReorderServiceContract $reorderService,
    ) {}

    public function index(AttributeIndexDto $dto): array
    {
        return $this->attributeService->index($dto);
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
