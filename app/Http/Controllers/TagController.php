<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagCreateRequest;
use App\Http\Requests\TagIndexRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class TagController extends Controller
{
    public function index(TagIndexRequest $request): JsonResource
    {
        $tags = Tag::search($request->validated())
            ->withCount('products')
            ->orderBy('products_count', 'DESC')
            ->paginate((int) $request->input('limit', 12));

        return TagResource::collection($tags);
    }

    public function store(TagCreateRequest $request): JsonResource
    {
        $tag = Tag::create($request->validated());

        return TagResource::make($tag);
    }

    public function update(Tag $tag, TagCreateRequest $request): JsonResource
    {
        $tag->update($request->validated());

        return TagResource::make($tag);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return Response::json(null, 204);
    }
}
