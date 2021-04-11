<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\StatusControllerSwagger;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusController extends Controller implements StatusControllerSwagger
{
    public function index(): JsonResource
    {
        return StatusResource::collection(Status::all());
    }

    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'required|string|size:6',
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
