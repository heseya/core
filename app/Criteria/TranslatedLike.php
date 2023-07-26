<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class TranslatedLike extends Criterion
{
    public function query(Builder $query): Builder
    {
        $localization = App::getLocale();

        return $query->where("{$this->key}->{$localization}", 'LIKE', '%' . $this->value . '%');
    }
}
