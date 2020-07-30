<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\StatusControllerSwagger;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusController extends Controller implements StatusControllerSwagger
{
    public function index(): JsonResource
    {
        $query = Status::select();

        return StatusResource::collection($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'required|string|size:6',
            'description' => 'string|max:255|nullable',
        ]);

        $status = Status::create($validated);

        return StatusResource::make($status)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Status $status, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:60',
            'color' => 'string|size:6',
            'description' => 'string|max:255|nullable',
        ]);

        $status->update($validated);

        return StatusResource::make($status);
    }

    public function destroy(Status $status)
    {
        $status->delete();

        return response()->json(null, 204);
    }
}
