<?php

namespace App\Http\Controllers;

use App\DTO\OrderStatus\OrderStatusDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\Error;
use App\Http\Requests\StatusIndexRequest;
use App\Http\Requests\StatusReorderRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use App\Services\Contracts\StatusServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class StatusController extends Controller
{
    public function __construct(
        private readonly StatusServiceContract $statusService,
    ) {}

    public function index(StatusIndexRequest $request): JsonResource
    {
        return StatusResource::collection(
            Status::searchByCriteria($request->validated())
                ->with(['metadata'])
                ->orderBy('order')
                ->get(),
        );
    }

    public function store(OrderStatusDto $dto): JsonResource
    {
        return StatusResource::make(
            $this->statusService->store($dto)
        );
    }

    public function update(Status $status, OrderStatusDto $dto): JsonResource
    {
        return StatusResource::make(
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
