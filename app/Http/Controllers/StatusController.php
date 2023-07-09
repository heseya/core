<?php

namespace App\Http\Controllers;

use App\Dtos\StatusDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\Error;
use App\Http\Requests\StatusCreateRequest;
use App\Http\Requests\StatusIndexRequest;
use App\Http\Requests\StatusReorderRequest;
use App\Http\Requests\StatusUpdateRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use App\Services\Contracts\TranslationServiceContract;
use App\Services\Contracts\StatusServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class StatusController extends Controller
{
    public function __construct(
    public function __construct(private StatusServiceContract $statusService) {}
        private TranslationServiceContract $translationService,
    ) {
    }
    public function index(StatusIndexRequest $request): JsonResource
    {
        $statuses = Status::searchByCriteria($request->validated())
            ->with(['metadata']);

        return StatusResource::collection(
            $statuses->orderBy('order')->get()
        );
    }

    public function store(StatusCreateRequest $request): JsonResource
    {
        return StatusResource::make(
            $this->statusService->store(StatusDto::instantiateFromRequest($request))
        );
    }

    public function update(Status $status, StatusUpdateRequest $request): JsonResource
    {
        return StatusResource::make(
            $this->statusService->update(
                $status,
                StatusDto::instantiateFromRequest($request)
            )
        );
    }

    public function reorder(StatusReorderRequest $request): JsonResponse
    {
        foreach ($request->input('statuses') as $key => $id) {
            Status::where('id', $id)->update(['order' => $key]);
        }

        return Response::json(null, 204);
    }

    public function destroy(Status $status): JsonResponse
    {
        if (Status::count() <= 1) {
            return Error::abort(
                'Musi istnieć co najmniej jeden status.',
                409,
            );
        }

        if ($status->orders()->count() > 0) {
            throw new ClientException(Exceptions::CLIENT_STATUS_USED);
        }

        $this->statusService->destroy($status);

        return Response::json(null, 204);
    }
}
