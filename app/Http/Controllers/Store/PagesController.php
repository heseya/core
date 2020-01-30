<?php

namespace App\Http\Controllers\Store;

use App\Page;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class PagesController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Page::select([
                'slug',
                'name',
            ])->where('public', true)
            ->simplePaginate(14)
        );
    }

    public function view(Page $page): JsonResponse
    {
        if ($page->public !== true) {
            abort(403);
        }

        $page->content = $page->parsed_content;

        return response()->json($page);
    }
}
