<?php

namespace App\Jobs;

use App\Enums\GoogleCategoriesLang;
use App\Services\Contracts\GoogleCategoryServiceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GoogleCategoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $categoryService = app(GoogleCategoryServiceContract::class);

        foreach (GoogleCategoriesLang::getValues() as $lang) {
            $categoryService->getGoogleProductCategory($lang, true);
        }
    }
}
