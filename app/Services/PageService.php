<?php

namespace App\Services;

use App\Models\Page;
use App\Services\Contracts\PageServiceContract;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageService implements PageServiceContract
{
    public function authorize(Page $page)
    {
        if (!Auth::check() && $page->public !== true) {
            throw new NotFoundHttpException;
        }
    }

    public function getPaginated(int $itemsPerPage = 14)
    {
        $query = Page::query();

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return $query->paginate($itemsPerPage);
    }

    public function create(array $attributes): Page
    {
        return Page::create($attributes);
    }

    public function update(Page $page, array $attributes): Page
    {
        $page->update($attributes);

        return $page;
    }

    public function delete(Page $page)
    {
        $page->delete();
    }
}
