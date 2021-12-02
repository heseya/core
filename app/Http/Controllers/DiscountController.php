<?php

namespace App\Http\Controllers;

use App\Events\DiscountCreated;
use App\Events\DiscountDeleted;
use App\Events\DiscountUpdated;
use App\Http\Requests\DiscountCreateRequest;
use App\Http\Requests\DiscountIndexRequest;
use App\Http\Requests\DiscountUpdateRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class DiscountController extends Controller
{
    public function index(DiscountIndexRequest $request): JsonResource
    {
        $query = Discount::search($request->validated())
            ->orderBy('updated_at', 'DESC')
            ->with('orders');

        return DiscountResource::collection(
            $query->paginate(Config::get('pagination.per_page')),
        );
    }

    public function show(Discount $discount): JsonResource
    {
        return DiscountResource::make($discount);
    }

    public function store(DiscountCreateRequest $request): JsonResource
    {
        $discount = Discount::create($request->validated());

        DiscountCreated::dispatch($discount);

        return DiscountResource::make($discount);
    }

    public function update(Discount $discount, DiscountUpdateRequest $request): JsonResource
    {
        $discount->update($request->validated());

        DiscountUpdated::dispatch($discount);

        return DiscountResource::make($discount);
    }

    public function destroy(Discount $discount): JsonResponse
    {
        if ($discount->delete()) {
            DiscountDeleted::dispatch($discount);
        }

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
