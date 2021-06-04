<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\PageControllerSwagger;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller implements PageControllerSwagger
{
    public function index(): JsonResource
    {
        $query = Page::query();

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return PageResource::collection(
            $query->paginate(14),
        );
    }

    public function show(Page $page): JsonResource
    {
        if (!Auth::check() && $page->public !== true) {
            throw new NotFoundHttpException;
        }

        return PageResource::make($page);
    }

    public function store(Request $request): JsonResource
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

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return Response::json(null, 204);
    }
}
