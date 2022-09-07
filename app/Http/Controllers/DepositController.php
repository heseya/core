<?php

namespace App\Http\Controllers;

use App\Events\ItemUpdatedQuantity;
use App\Http\Requests\DepositCreateRequest;
use App\Http\Requests\DepositIndexRequest;
use App\Http\Resources\DepositResource;
use App\Models\Deposit;
use App\Models\Item;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class DepositController extends Controller
{
    public function index(DepositIndexRequest $request): JsonResource
    {
        $deposits = Deposit::searchByCriteria($request->validated())
            ->with(['item', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(Config::get('pagination.per_page'));

        return DepositResource::collection($deposits);
    }

    public function show(Item $item): JsonResource
    {
        return DepositResource::collection(
            $item->deposits()->paginate(Config::get('pagination.per_page')),
        );
    }

    public function store(Item $item, DepositCreateRequest $request): JsonResource
    {
        $deposit = $item->deposits()->create($request->validated());

        ItemUpdatedQuantity::dispatch($item);

        return DepositResource::make($deposit);
    }
}
