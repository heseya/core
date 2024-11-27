<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Controllers;

use App\Http\Controllers\Controller;
use Domain\Manufacturer\Dtos\ManufacturerCreateDto;
use Domain\Manufacturer\Dtos\ManufacturerIndexDto;
use Domain\Manufacturer\Dtos\ManufacturerUpdateDto;
use Domain\Manufacturer\Models\Manufacturer;
use Domain\Manufacturer\Resources\ManufacturerResource;
use Domain\Manufacturer\Services\ManufacturerService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class ManufacturerController extends Controller
{
    public function __construct(
        private ManufacturerService $manufacturerService,
    ) {}

    public function index(ManufacturerIndexDto $dto): JsonResource
    {
        return ManufacturerResource::collection($this->manufacturerService->index($dto));
    }

    public function store(ManufacturerCreateDto $dto): JsonResource
    {
        return ManufacturerResource::make($this->manufacturerService->create($dto));
    }

    public function show(Manufacturer $manufacturer): JsonResource
    {
        return ManufacturerResource::make($manufacturer);
    }

    public function update(Manufacturer $manufacturer, ManufacturerUpdateDto $dto): JsonResource
    {
        return ManufacturerResource::make($this->manufacturerService->update($manufacturer, $dto));
    }

    public function destroy(Manufacturer $manufacturer): HttpResponse
    {
        $this->manufacturerService->destroy($manufacturer);

        return Response::noContent();
    }
}
