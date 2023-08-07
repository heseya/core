<?php

declare(strict_types=1);

namespace Domain\Page;

use App\Services\Contracts\TranslationServiceContract;
use Domain\Metadata\MetadataService;
use Domain\Page\Dtos\PageCreateDto;
use Domain\Page\Dtos\PageUpdateDto;
use Domain\Page\Events\PageCreated;
use Domain\Page\Events\PageDeleted;
use Domain\Page\Events\PageUpdated;
use Domain\Seo\SeoMetadataService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Optional;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PageService
{
    public function __construct(
        private MetadataService $metadataService,
        private SeoMetadataService $seoMetadataService,
        private TranslationServiceContract $translationService,
    ) {}

    public function authorize(Page $page): void
    {
        if (!Auth::user()?->can('pages.show_hidden') && $page->public !== true) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param array<string, string>|null $search
     *
     * @return LengthAwarePaginator<Page>
     */
    public function getPaginated(?array $search): LengthAwarePaginator
    {
        $query = Page::query()
            ->whereDoesntHave('products')
            ->with(['seo', 'metadata'])
            ->orderBy('order')
            ->searchByCriteria($search ?? []);

        if (!Auth::user()?->can('pages.show_hidden')) {
            $query->where('public', true);
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    public function create(PageCreateDto $dto): Page
    {
        $attributes = $dto->toArray();
        $pageCurrentOrder = Page::query()->orderByDesc('order')->value('order');
        if ($pageCurrentOrder !== null) {
            $attributes = array_merge($attributes, ['order' => $pageCurrentOrder + 1]);
        }

        $page = new Page($attributes);

        foreach ($dto->translations as $lang => $translations) {
            $page->setLocale($lang)->fill($translations);
        }

        $this->translationService->checkPublished($page, ['name', 'content_html']);

        $page->save();

        if (!($dto->seo instanceof Optional)) {
            $this->seoMetadataService->createOrUpdateFor($page, $dto->seo);
        }

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync(Page::class, $page->getKey(), $dto->metadata);
        }

        PageCreated::dispatch($page);

        return $page;
    }

    public function update(Page $page, PageUpdateDto $dto): Page
    {
        $page->fill($dto->toArray());

        if (!$dto->translations instanceof Optional) {
            foreach ($dto->translations as $lang => $translations) {
                $page->setLocale($lang)->fill($translations);
            }
            $this->translationService->checkPublished($page, ['name', 'content_html']);
        }

        $page->save();

        $seo = $page->seo;
        if ($seo !== null && !$dto->seo instanceof Optional) {
            $this->seoMetadataService->update($dto->seo, $seo);
        }

        PageUpdated::dispatch($page);

        return $page;
    }

    public function delete(Page $page): void
    {
        if ($page->delete()) {
            PageDeleted::dispatch($page);
            if ($page->seo !== null) {
                $this->seoMetadataService->delete($page->seo);
            }
            $page->slug .= '_' . $page->deleted_at;
            $page->save();
        }
    }
}
