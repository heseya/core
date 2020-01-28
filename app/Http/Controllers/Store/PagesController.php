<?php

namespace App\Http\Controllers\Store;

use App\Page;
use Parsedown;
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
            ])->simplePaginate(14)
        );
    }

    public function view(Page $page): JsonResponse
    {
        $parsedown = new Parsedown();
        $page->content = $parsedown->text($page->content);

        return response()->json($page);
    }
}
