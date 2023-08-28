<?php

declare(strict_types=1);

namespace Domain\Tag\Repositories;

use App\Traits\GetPublishedLanguageFilter;
use Domain\Tag\Dtos\TagCreateDto;
use Domain\Tag\Dtos\TagIndexDto;
use Domain\Tag\Dtos\TagUpdateDto;
use Domain\Tag\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Optional;

final class TagRepository
{
    use GetPublishedLanguageFilter;

    /**
     * @return LengthAwarePaginator<Tag>
     */
    public function getAll(TagIndexDto $dto): LengthAwarePaginator
    {
        return Tag::searchByCriteria($dto->toArray() + $this->getPublishedLanguageFilter('tags'))
            ->withCount('products')
            ->orderBy('products_count', 'DESC')
            ->paginate(Config::get('pagination.per_page'));
    }

    public function store(TagCreateDto $dto): Tag
    {
        $tag = new Tag($dto->toArray());

        foreach ($dto->translations as $lang => $translation) {
            $tag->setLocale($lang)->fill($translation);
        }
        $tag->save();

        return $tag;
    }

    public function update(Tag $tag, TagUpdateDto $dto): Tag
    {
        if (!($dto->translations instanceof Optional)) {
            foreach ($dto->translations as $lang => $translation) {
                $tag->setLocale($lang)->fill($translation);
            }
        }

        $tag->fill($dto->toArray());
        $tag->save();

        return $tag;
    }

    public function destroy(Tag $tag): void
    {
        $tag->delete();
    }
}
