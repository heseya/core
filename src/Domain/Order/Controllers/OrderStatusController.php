<?php

declare(strict_types=1);

namespace Domain\Order\Controllers;

use App\DTO\ReorderDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\Error;
use App\Http\Controllers\Controller;
use App\Models\Status;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\StatusServiceContract;
use App\Traits\GetPublishedLanguageFilter;
use Domain\Order\Dtos\OrderStatusCreateDto;
use Domain\Order\Dtos\OrderStatusIndexDto;
use Domain\Order\Dtos\OrderStatusReorderDto;
use Domain\Order\Dtos\OrderStatusUpdateDto;
use Domain\Order\Resources\OrderStatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class OrderStatusController extends Controller
{
    use GetPublishedLanguageFilter;

    public function __construct(
        private readonly StatusServiceContract $statusService,
        private readonly ReorderServiceContract $reorderService,
    ) {}

    public function index(OrderStatusIndexDto $dto): JsonResource
    {
        return OrderStatusResource::collection(
            Status::searchByCriteria($dto->toArray() + $this->getPublishedLanguageFilter('statuses'))
                ->with(['metadata', 'metadataPrivate'])
                ->orderBy('order')
                ->get(),
        );
    }

    public function store(OrderStatusCreateDto $dto): JsonResource
    {
        return OrderStatusResource::make(
            $this->statusService->store($dto)
        );
    }

    public function update(Status $status, OrderStatusUpdateDto $dto): JsonResource
    {
        return OrderStatusResource::make(
            $this->statusService->update($status, $dto),
        );
    }

    /**
     * @throws ClientException
     */
    public function destroy(Status $status): HttpResponse|JsonResponse
    {
        if (Status::query()->count() <= 1) {
            return Error::abort(
                'Musi istnieć co najmniej jeden status.',
                409,
            );
        }

        if ($status->orders()->count() > 0) {
            throw new ClientException(Exceptions::CLIENT_STATUS_USED);
        }

        $this->statusService->destroy($status);

        return Response::noContent();
    }

    public function reorder(OrderStatusReorderDto $request): HttpResponse
    {
        $dto = new ReorderDto($request->statuses);

        $this->reorderService->reorderAndSave(Status::class, $dto);

        return Response::noContent();
    }
}
