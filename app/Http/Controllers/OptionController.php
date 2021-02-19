<?php

namespace App\Http\Controllers;

use App\Http\Requests\OptionStoreRequest;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionController extends Controller
{
    public function store(OptionStoreRequest $request): JsonResource
    {
        $option = Option::create($request->validated());

        return OptionResource::make($option);
    }

    public function show(Option $option): JsonResource
    {
        return OptionResource::make($option);
    }

    public function update(OptionStoreRequest $request, Option $option): JsonResource
    {
        $option->update($request->validated());

        return OptionResource::make($option);
    }

    public function destroy(Option $option): JsonResponse
    {
        $option->delete();

        return response()->json(null, 204);
    }
}
