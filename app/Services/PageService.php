<?php

namespace App\Services;

use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageService implements PageServiceContract
{
    public function authorize(Page $page): void
    {
        if (!Auth::user()->can('pages.show_hidden') && $page->public !== true) {
            throw new NotFoundHttpException();
        }
    }

    public function getPaginated(): LengthAwarePaginator
    {
        $query = Page::query();

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

        return Page::create($attributes);
    }

    public function update(Page $page, array $attributes): Page
    {
        $page->update($attributes);

        return $page;
    }

    public function delete(Page $page): void
    {
        $page->delete();
    }

    public function reorder(array $pages): void
    {
        foreach ($pages as $key => $id) {
            Page::where('id', $id)->update(['order' => $key]);
        }
    }
}
