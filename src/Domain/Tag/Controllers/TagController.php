<?php

declare(strict_types=1);

namespace Domain\Tag\Controllers;

use App\Http\Controllers\Controller;
use Domain\Tag\Dtos\TagCreateDto;
use Domain\Tag\Dtos\TagIndexDto;
use Domain\Tag\Dtos\TagUpdateDto;
use Domain\Tag\Models\Tag;
use Domain\Tag\Resources\TagResource;
use Domain\Tag\Services\TagService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class TagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService,
    ) {}

    public function index(TagIndexDto $dto): JsonResource
    {
        return TagResource::collection($this->tagService->getAll($dto));
    }

    public function store(TagCreateDto $dto): JsonResource
    {
        return TagResource::make($this->tagService->store($dto));
    }

    public function update(Tag $tag, TagUpdateDto $dto): JsonResource
    {
        return TagResource::make($this->tagService->update($tag, $dto));
    }

    public function destroy(Tag $tag): HttpResponse
    {
        $this->tagService->destroy($tag);

        return Response::noContent();
    }
}
