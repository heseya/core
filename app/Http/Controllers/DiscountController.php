<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\DiscountControllerSwagger;
use App\Http\Requests\DiscountCreateRequest;
use App\Http\Requests\DiscountIndexRequest;
use App\Http\Requests\DiscountUpdateRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountController extends Controller implements DiscountControllerSwagger
{
    public function index(DiscountIndexRequest $request): JsonResource
    {
        $query = Discount::search($request->validated());

        return DiscountResource::collection($query->paginate($request->input('limit', 12)));
    }

    public function show(Discount $discount): JsonResource
    {
        return DiscountResource::make($discount);
    }

    public function store(DiscountCreateRequest $request): JsonResource
    {
        $discount = Discount::create($request->validated());

        return DiscountResource::make($discount);
    }

    public function update(Discount $discount, DiscountUpdateRequest $request): JsonResource
    {
        $discount->update($request->validated());

        return DiscountResource::make($discount);
    }
}
