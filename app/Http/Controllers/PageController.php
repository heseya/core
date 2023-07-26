<?php

namespace App\Http\Controllers;

use App\DTO\Page\PageCreateDto;
use App\DTO\Page\PageUpdateDto;
use App\Http\Requests\PageIndexRequest;
use App\Http\Requests\PageReorderRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
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

    public function store(PageCreateDto $dto): JsonResource
    {
        $page = $this->pageService->create($dto);

        return PageResource::make($page);
    }

    public function update(Page $page, PageUpdateDto $dto): JsonResource
    {
        $page = $this->pageService->update($page, $dto);

        return PageResource::make($page);
    }

    public function destroy(Page $page): HttpResponse
    {
        $this->pageService->delete($page);

        return Response::noContent();
    }

    public function reorder(PageReorderRequest $request): HttpResponse
    {
        $this->pageService->reorder($request->input('pages'));

        return Response::noContent();
    }
}
