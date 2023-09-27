<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class BannerWithTranslations extends Criterion
{
    public function query(Builder $query): Builder
    {
        if ($this->value) {
            return $query;
        }

        return $query->whereHas('bannerMedia', function (Builder $query): void {
            $query->whereJsonContains('published', App::getLocale());
        });
    }
}
