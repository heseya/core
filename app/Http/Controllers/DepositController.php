<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\DepositControllerSwagger;
use App\Http\Requests\DepositCreateRequest;
use App\Http\Resources\DepositResource;
use App\Models\Deposit;
use App\Models\Item;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositController extends Controller implements DepositControllerSwagger
{
    public function index(): JsonResource
    {
        return DepositResource::collection(
            Deposit::paginate(12),
        );
    }

    public function show(Item $item): JsonResource
    {
        return DepositResource::collection(
            $item->deposits()->paginate(12),
        );
    }

    public function store(Item $item, DepositCreateRequest $request): JsonResource
    {
        $deposit = $item->deposits()->create($request->validated());

        return DepositResource::make($deposit);
    }
}
