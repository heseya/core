<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\PageControllerSwagger;
use App\Http\Requests\PageOrderRequest;
use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class PageController extends Controller implements PageControllerSwagger
{
    private PageServiceContract $pageService;

    public function __construct(PageServiceContract $pageService)
    {
        $this->pageService = $pageService;
    }

    public function index(): JsonResource
    {
        return PageResource::collection(
            $this->pageService->getPaginated(),
        );
    }

    public function show(Page $page): JsonResource
    {
        $this->pageService->authorize($page);

        return PageResource::make($page);
    }

    public function store(PageStoreRequest $request): JsonResource
    {
        $page = $this->pageService->create($request->validated());

        return PageResource::make($page);
    }

    public function update(Page $page, PageUpdateRequest $request): JsonResource
    {
        $page = $this->pageService->update($page, $request->validated());

        return PageResource::make($page);
    }

    public function destroy(Page $page): JsonResponse
    {
        $this->pageService->delete($page);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function reorder(PageOrderRequest $request): JsonResponse
    {
        $this->pageService->reorder($request->input('pages'));

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
