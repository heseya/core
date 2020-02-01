<?php

namespace App\Http\Controllers\Store;

use App\Page;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PagesController extends Controller
{
    public function index(): ResourceCollection
    {
        return PageResource::collection(
            Page::where('public', true)->simplePaginate(14)
        );
    }

    public function view(Page $page): JsonResponse
    {
        if ($page->public !== true) {
            abort(403);
        }

        return response()->json([
            'name' => $page->name,
            'slug' => $page->slug,
            'content' => $page->parsed_content,
        ]);
    }
}
