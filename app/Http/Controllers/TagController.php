<?php

namespace App\Http\Controllers;

use App\Events\ProductSearchValueEvent;
use App\Http\Requests\TagCreateRequest;
use App\Http\Requests\TagIndexRequest;
use App\Http\Requests\TagUpdateRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class TagController extends Controller
{
    public function index(TagIndexRequest $request): JsonResource
    {
        $tags = Tag::searchByCriteria($request->validated())
            ->withCount('products')
            ->orderBy('products_count', 'DESC')
            ->paginate(Config::get('pagination.per_page'));

        return TagResource::collection($tags);
    }

    public function store(TagCreateRequest $request): JsonResource
    {
        $tag = Tag::query()->create($request->validated());

        return TagResource::make($tag);
    }

    public function update(Tag $tag, TagUpdateRequest $request): JsonResource
    {
        $tag->update($request->validated());
        ProductSearchValueEvent::dispatch($tag->products->pluck('id')->toArray());

        return TagResource::make($tag);
    }

    public function destroy(Tag $tag): HttpResponse
    {
        $products = $tag->products->pluck('id')->toArray();
        $tag->delete();
        ProductSearchValueEvent::dispatch($products);

        return Response::noContent();
    }
}
