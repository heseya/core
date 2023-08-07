<?php

declare(strict_types=1);

namespace Domain\Page;

use App\DTO\ReorderDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\PageReorderRequest;
use App\Services\ReorderService;
use Domain\Page\Dtos\PageCreateDto;
use Domain\Page\Dtos\PageUpdateDto;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly ReorderService $reorderService,
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
        $dto = ReorderDto::from([
            'ids' => $request->input('pages'),
        ]);

        $this->reorderService->reorderAndSave(Page::class, $dto);

        return Response::noContent();
    }
}
