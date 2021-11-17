<?php

namespace App\Services;

use App\Dtos\PageDto;
use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageService implements PageServiceContract
{
    public function __construct(
        protected SeoMetadataServiceContract $seoMetadataService,
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

        $page = Page::create($attributes);

        $page->seo()->save($this->seoMetadataService->create($dto->getSeo()));

        return $page;
    }

    public function update(Page $page, PageDto $dto): Page
    {
        $page->update($dto->toArray());

        $seo = $page->seo;
        if ($seo !== null) {
            $this->seoMetadataService->update($dto->getSeo(), $seo);
        }

        return $page;
    }

    public function delete(Page $page): void
    {
        $page->delete();

        if ($page->seo !== null) {
            $this->seoMetadataService->delete($page->seo);
        }
    }

    public function reorder(array $pages): void
    {
        foreach ($pages as $key => $id) {
            Page::where('id', $id)->update(['order' => $key]);
        }
    }
}
