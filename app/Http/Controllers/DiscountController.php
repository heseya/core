<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\DiscountControllerSwagger;
use App\Http\Requests\DiscountCreateRequest;
use App\Http\Requests\DiscountIndexRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountController extends Controller implements DiscountControllerSwagger
{
    public function index(DiscountIndexRequest $request): JsonResource
    {
        $query = Discount::search($request->validated());

        return DiscountResource::collection($query->get());
    }

    public function store(DiscountCreateRequest $request): JsonResource
    {
        $discount = Discount::create($request->validated());

        return DiscountResource::make($discount);
    }
}
