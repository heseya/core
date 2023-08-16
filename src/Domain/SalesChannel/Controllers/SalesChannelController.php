<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Controllers;

use App\Http\Controllers\Controller;
use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelIndexDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Domain\SalesChannel\SalesChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

final class SalesChannelController extends Controller
{
    public function __construct(
        private readonly SalesChannelService $salesChannelService,
    ) {}

    public function index(SalesChannelIndexDto $dto): SalesChannelResource
    {
        return new SalesChannelResource($this->salesChannelService->index(
            $dto,
            Gate::denies('sales_channels.show_hidden'),
        ));
    }

    public function store(SalesChannelCreateDto $dto): SalesChannelResource
    {
        return new SalesChannelResource(
            $this->salesChannelService->store($dto)
        );
    }

    public function update(string $id, SalesCHannelUpdateDto $dto): SalesChannelResource
    {
        $this->salesChannelService->update($id, $dto);

        return new SalesChannelResource(
            $this->salesChannelService->show($id),
        );
    }

    public function destroy(string $id): HttpResponse|JsonResponse
    {
        $this->salesChannelService->delete($id);

        return Response::noContent();
    }
}
