<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Controllers;

use App\Exceptions\ClientException;
use App\Http\Controllers\Controller;
use Domain\Order\Dtos\OrderStatusUpdateDto;
use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Domain\SalesChannel\SalesChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class SalesChannelController extends Controller
{
    public function __construct(
        private readonly SalesChannelService $salesChannelService,
    ) {}

    public function index(): SalesChannelResource
    {
        return new SalesChannelResource(
            $this->salesChannelService->index(),
        );
    }

    public function store(SalesChannelCreateDto $dto): SalesChannelResource
    {
        return new SalesChannelResource(
            $this->salesChannelService->store($dto)
        );
    }

    public function update(string $id, OrderStatusUpdateDto $dto): SalesChannelResource
    {
        return new SalesChannelResource(
            $this->salesChannelService->update($id, $dto),
        );
    }

    /**
     * @throws ClientException
     */
    public function destroy(string $id): HttpResponse|JsonResponse
    {
        $this->salesChannelService->destroy($id);

        return Response::noContent();
    }
}
