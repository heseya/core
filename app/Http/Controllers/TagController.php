<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagIndexRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

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
}
