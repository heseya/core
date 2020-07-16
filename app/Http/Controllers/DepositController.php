<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\DepositControllerSwagger;
use App\Http\Resources\DepositResource;
use App\Models\Deposit;
use App\Models\Item;
use Illuminate\Http\Request;
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

    public function store(Item $item, Request $request)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
        ]);

        $deposit = $item->deposits()->create($validated);

        return DepositResource::make($deposit)
            ->response()
            ->setStatusCode(201);
    }
}
