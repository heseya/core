<?php

namespace App\Observers;

use App\Models\Page;

class PageObserver
{
    /**
     * Handle the Page "deleted" event.
     *
     * @param Page $page
     *
     * @return void
     */
    public function deleted(Page $page): void
    {
        $page->slug .= '_'.$page->deleted_at;
        $page->save();
    }
}
