<?php

namespace App\Services;

use App\Dtos\PageDto;
use App\Events\PageCreated;
use App\Events\PageDeleted;
use App\Events\PageUpdated;
use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageService implements PageServiceContract
{
    public function __construct(
        protected SeoMetadataServiceContract $seoMetadataService,
        protected TranslationServiceContract $translationService,
    ) {
    }

    public function authorize(Page $page): void
    {
        if (!Auth::user()->can('pages.show_hidden') && $page->public !== true) {
            throw new NotFoundHttpException();
        }
    }

    public function getPaginated(): LengthAwarePaginator
    {
        $query = Page::query()->with('seo');

        if (!Auth::user()->can('pages.show_hidden')) {
            $query->where('public', true);
        }

        return $query->sort('order')->paginate(Config::get('pagination.per_page'));
    }

    public function create(PageDto $dto): Page
    {
        $attributes = $dto->toArray();
        $pageCurrentOrder = Page::orderByDesc('order')->value('order');
        if ($pageCurrentOrder !== null) {
            $attributes = array_merge($attributes, ['order' => $pageCurrentOrder + 1]);
        }

        $page = Page::make($attributes);

        foreach ($dto->getTranslations() as $lang => $translations) {
            $page->setLocale($lang)->fill($translations->toArray());
        }

        $this->translationService->checkPublished($page, ['name', 'content_html']);

        $page->save();

        $page->seo()->save($this->seoMetadataService->create($dto->getSeo()));

        PageCreated::dispatch($page);

        return $page;
    }

    public function update(Page $page, PageDto $dto): Page
    {
        $page->fill($dto->toArray());

        foreach ($dto->getTranslations() as $lang => $translations) {
            $page->setLocale($lang)->fill($translations->toArray());
        }

        $this->translationService->checkPublished($page, ['name', 'content_html']);

        $page->save();

        $seo = $page->seo;
        if ($seo !== null) {
            $this->seoMetadataService->update($dto->getSeo(), $seo);
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
        }
    }

    public function reorder(array $pages): void
    {
        foreach ($pages as $key => $id) {
            Page::where('id', $id)->update(['order' => $key]);
        }
    }
}
