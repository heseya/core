<?php

namespace App\Services;

use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageService implements PageServiceContract
{
    protected SeoMetadataServiceContract $seoMetadataService;

    public function __construct(SeoMetadataServiceContract $seoMetadataService)
    {
        $this->seoMetadataService = $seoMetadataService;
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

    public function create(array $attributes): Page
    {
        $pageCurrentOrder = Page::orderByDesc('order')->value('order');
        if ($pageCurrentOrder !== null) {
            $attributes = array_merge($attributes, ['order' => $pageCurrentOrder + 1]);
        }

        $page = Page::create($attributes);

        $attributes['seo']['model_id'] = $page->getKey();
        $attributes['seo']['model_type'] = $page::class;
        $this->seoMetadataService->create($attributes['seo']);

        return $page;
    }

    public function update(Page $page, array $attributes): Page
    {
        $page->update($attributes);

        if (array_key_exists('seo', $attributes)) {
            $this->seoMetadataService->update($attributes['seo'], $page->seo);
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
