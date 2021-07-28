<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\StatusControllerSwagger;
use App\Http\Requests\StatusOrderRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusController extends Controller implements StatusControllerSwagger
{
    public function index(): JsonResource
    {
        return StatusResource::collection(Status::orderBy('order')->get());
    }

    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'required|string|size:6',
            'cancel' => 'boolean',
            'description' => 'string|max:255|nullable',
        ]);

        $status = Status::create($validated);

        return StatusResource::make($status);
    }

    public function update(Status $status, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:60',
            'color' => 'string|size:6',
            'cancel' => 'boolean',
            'description' => 'string|max:255|nullable',
        ]);

        $status->update($validated);

        return StatusResource::make($status);
    }

    public function order(StatusOrderRequest $request): JsonResponse
    {
        foreach ($request->input('statuses') as $key => $id) {
            Status::where('id', $id)->update(['order' => $key]);
        }

        return response()->json(null, 204);
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

        return response()->json(null, 204);
    }
}
