<?php

namespace App\Http\Controllers;

use App\Dtos\PageCreateDto;
use App\Dtos\PageUpdateDto;
use App\Http\Requests\PageIndexRequest;
use App\Http\Requests\PageReorderRequest;
use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly PageServiceContract $pageService,
    ) {}

    public function index(PageIndexRequest $request): JsonResource
    {
        return PageResource::collection(
            $this->pageService->getPaginated($request->validated()),
        );
    }

    public function show(Page $page): JsonResource
    {
        $this->pageService->authorize($page);

        return PageResource::make($page);
    }

    public function store(PageStoreRequest $request): JsonResource
    {
        $dto = PageCreateDto::instantiateFromRequest($request);
        $page = $this->pageService->create($dto);

        return PageResource::make($page);
    }

    public function update(Page $page, PageUpdateRequest $request): JsonResource
    {
        $dto = PageUpdateDto::instantiateFromRequest($request);
        $page = $this->pageService->update($page, $dto);

        return PageResource::make($page);
    }

    public function destroy(Page $page): \Illuminate\Http\Response
    {
        $this->pageService->delete($page);

        return Response::noContent();
    }

    public function reorder(PageReorderRequest $request): \Illuminate\Http\Response
    {
        $this->pageService->reorder($request->input('pages'));

        return Response::noContent();
    }
}
