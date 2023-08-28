<?php

declare(strict_types=1);

namespace Domain\Tag\Services;

use Domain\Tag\Dtos\TagCreateDto;
use Domain\Tag\Dtos\TagIndexDto;
use Domain\Tag\Dtos\TagUpdateDto;
use Domain\Tag\Models\Tag;
use Domain\Tag\Repositories\TagRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class TagService
{
    public function __construct(
        private TagRepository $tagRepository,
    ) {}

    /**
     * @return LengthAwarePaginator<Tag>
     */
    public function getAll(TagIndexDto $dto): LengthAwarePaginator
    {
        return $this->tagRepository->getAll($dto);
    }

    public function store(TagCreateDto $dto): Tag
    {
        return $this->tagRepository->store($dto);
    }

    public function update(Tag $tag, TagUpdateDto $dto): Tag
    {
        return $this->tagRepository->update($tag, $dto);
    }

    public function destroy(Tag $tag): void
    {
        $this->tagRepository->destroy($tag);
    }
}
