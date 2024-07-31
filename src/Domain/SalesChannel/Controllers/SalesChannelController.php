<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Controllers;

use App\Http\Controllers\Controller;
use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelIndexDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Domain\SalesChannel\SalesChannelCrudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

final class SalesChannelController extends Controller
{
    public function __construct(
        private readonly SalesChannelCrudService $salesChannelService,
    ) {}

    public function index(SalesChannelIndexDto $dto): JsonResource
    {
        return SalesChannelResource::collection($this->salesChannelService->index(
            $dto,
            Gate::denies('sales_channels.show_hidden'),
        ));
    }

    public function show(SalesChannel $salesChannel): SalesChannelResource
    {
        return new SalesChannelResource(
            $this->salesChannelService->show($salesChannel->getKey()),
        );
    }

    public function store(SalesChannelCreateDto $dto): SalesChannelResource
    {
        return new SalesChannelResource(
            $this->salesChannelService->store($dto),
        );
    }

    public function update(SalesChannel $salesChannel, SalesChannelUpdateDto $dto): SalesChannelResource
    {
        return SalesChannelResource::make($this->salesChannelService->update($salesChannel, $dto));
    }

    public function destroy(SalesChannel $salesChannel): HttpResponse|JsonResponse
    {
        $this->salesChannelService->delete($salesChannel);

        return Response::noContent();
    }
}
