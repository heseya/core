<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class TranslatedLike extends Search
{
    public function query(Builder $query): Builder
    {
        $localization = App::getLocale();

        return $query->where("{$this->key}->{$localization}", 'LIKE', '%' . $this->value . '%');
    }
}
