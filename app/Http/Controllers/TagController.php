<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagCreateRequest;
use App\Http\Requests\TagIndexRequest;
use App\Http\Requests\TagUpdateRequest;
use App\Http\Resources\TagResource;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
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

        // @phpstan-ignore-next-line
        $tag->products()->searchable();

        return TagResource::make($tag);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $productsIds = $tag->products()->pluck('id');
        $tag->delete();

        // @phpstan-ignore-next-line
        Product::query()->whereIn('id', $productsIds)->searchable();

        return Response::json(null, 204);
    }
}
