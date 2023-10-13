<?php

declare(strict_types=1);

namespace Domain\Tag\Repositories;

use App\Exceptions\PublishingException;
use App\Services\Contracts\TranslationServiceContract;
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

    public function __construct(
        protected TranslationServiceContract $translationService,
    ) {}

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

    /**
     * @throws PublishingException
     */
    public function store(TagCreateDto $dto): Tag
    {
        $tag = new Tag($dto->toArray());

        foreach ($dto->translations as $lang => $translations) {
            foreach ($translations as $key => $translation) {
                $tag->setTranslation($key, $lang, $translation);
            }
        }

        $this->translationService->checkPublished($tag, ['name']);

        $tag->save();

        return $tag;
    }

    /**
     * @throws PublishingException
     */
    public function update(Tag $tag, TagUpdateDto $dto): Tag
    {
        $tag->fill($dto->toArray());
        if (!($dto->translations instanceof Optional)) {
            foreach ($dto->translations as $lang => $translations) {
                foreach ($translations as $key => $translation) {
                    $tag->setTranslation($key, $lang, $translation);
                }
            }
        }

        $this->translationService->checkPublished($tag, ['name']);

        $tag->save();

        return $tag;
    }

    public function destroy(Tag $tag): void
    {
        $tag->delete();
    }
}