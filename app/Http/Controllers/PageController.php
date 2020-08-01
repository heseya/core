<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\PageControllerSwagger;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller implements PageControllerSwagger
{
    public function index(): JsonResource
    {
        $query = Page::select();

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return PageResource::collection($query->simplePaginate(14));
    }

    public function show(Page $page)
    {
        if (!Auth::check() && $page->public !== true) {
            return Error::abort('Unauthorized.', 401);
        }

        return PageResource::make($page);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'public' => 'boolean',
            'content_md' => 'string|nullable',
        ]);

        $page = Page::create($validated);

        return PageResource::make($page);
    }

    public function update(Page $page, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255',
            'public' => 'boolean',
            'content_md' => 'string|nullable',
        ]);

        $page->update($validated);

        return PageResource::make($page);
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json(null, 204);
    }
}
