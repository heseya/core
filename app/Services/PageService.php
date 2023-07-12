<?php

namespace App\Services;

use App\DTO\Page\PageCreateDto;
use App\DTO\Page\PageUpdateDto;
use App\Events\PageCreated;
use App\Events\PageDeleted;
use App\Events\PageUpdated;
use App\Models\Page;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\PageServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Optional;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageService implements PageServiceContract
{
    public function __construct(
        protected SeoMetadataServiceContract $seoMetadataService,
        protected MetadataServiceContract $metadataService,
        protected TranslationServiceContract $translationService,
    ) {}

    public function authorize(Page $page): void
    {
        if (!Auth::user()?->can('pages.show_hidden') && $page->public !== true) {
            throw new NotFoundHttpException();
        }
    }

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
            $page->setLocale($lang)->fill($translations->toArray());
        }

        $this->translationService->checkPublished($page, ['name', 'content_html']);

        $page->save();

        if (!($dto->seo instanceof Optional)) {
            $this->seoMetadataService->createOrUpdateFor($page, $dto->seo);
        }

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($page, $dto->metadata);
        }

        PageCreated::dispatch($page);

        return $page;
    }

    public function update(Page $page, PageUpdateDto $dto): Page
    {
        $page->fill($dto->toArray());

        if (!$dto->translations instanceof Optional) {
            foreach ($dto->translations as $lang => $translations) {
                $page->setLocale($lang)->fill($translations->toArray());
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

    public function reorder(array $pages): void
    {
        foreach ($pages as $key => $id) {
            Page::query()->where('id', $id)->update(['order' => $key]);
        }
    }
}
