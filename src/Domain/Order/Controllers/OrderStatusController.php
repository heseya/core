<?php

declare(strict_types=1);

namespace Domain\Order\Controllers;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\Error;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatusIndexRequest;
use App\Http\Requests\StatusReorderRequest;
use App\Models\Status;
use App\Services\Contracts\StatusServiceContract;
use Domain\Order\Dtos\OrderStatusCreateDto;
use Domain\Order\Dtos\OrderStatusUpdateDto;
use Domain\Order\Resources\OrderStatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class OrderStatusController extends Controller
{
    public function __construct(
        private readonly StatusServiceContract $statusService,
    ) {}

    public function index(StatusIndexRequest $request): JsonResource
    {
        return OrderStatusResource::collection(
            Status::searchByCriteria($request->validated())
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

    public function reorder(StatusReorderRequest $request): HttpResponse
    {
        foreach ($request->input('statuses') as $key => $id) {
            Status::query()->where('id', $id)->update(['order' => $key]);
        }

        return Response::noContent();
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
}
