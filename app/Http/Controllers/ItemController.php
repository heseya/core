<?php

namespace App\Http\Controllers;

use App\Dtos\ItemDto;
use App\Http\Requests\ItemCreateRequest;
use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemUpdateRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\Contracts\ItemServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class ItemController extends Controller
{
    public function __construct(private ItemServiceContract $itemService)
    {
    }

    public function index(ItemIndexRequest $request): JsonResource
    {
        $items = Item::searchByCriteria($request->validated())
            ->sort($request->input('sort', 'sku'))
            ->with(['deposits', 'metadata', 'metadataPrivate', 'groupedDeposits']);

        return ItemResource::collection(
            $items->paginate(Config::get('pagination.per_page')),
        );
    }

    public function show(Item $item): JsonResource
    {
        $item->load([
            'options',
            'options.schema',
            'products',
            'products.media',
        ]);

        return ItemResource::make($item);
    }

    public function store(ItemCreateRequest $request): JsonResource
    {
        $item = $this->itemService->store(
            ItemDto::instantiateFromRequest($request)
        );

        return ItemResource::make($item);
    }

    public function update(Item $item, ItemUpdateRequest $request): JsonResource
    {
        $item = $this->itemService->update(
            $item,
            ItemDto::instantiateFromRequest($request)
        );

        return ItemResource::make($item);
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->itemService->destroy($item);

        return Response::json(null, 204);
    }
}
