<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Requests\StatusCreateRequest;
use App\Http\Requests\StatusReorderRequest;
use App\Http\Requests\StatusUpdateRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class StatusController extends Controller
{
    public function index(): JsonResource
    {
        return StatusResource::collection(Status::orderBy('order')->get());
    }

    public function store(StatusCreateRequest $request): JsonResource
    {
        $status = Status::create($request->validated());

        return StatusResource::make($status);
    }

    public function update(Status $status, StatusUpdateRequest $request): JsonResource
    {
        $status->update($request->validated());

        return StatusResource::make($status);
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
            return Error::abort(
                'Status nie może być usunięty, ponieważ jest przypisany do zamówienia.',
                409,
            );
        }

        $status->delete();

        return Response::json(null, 204);
    }
}
