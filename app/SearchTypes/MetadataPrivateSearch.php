<?php

namespace App\SearchTypes;

use App\Traits\PermissionUtility;
use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class MetadataPrivateSearch extends Search
{
    use PermissionUtility;

    public function query(Builder $query): Builder
    {
        if ($this->deniesAbilityByModel('show_metadata_private', $query->getModel())) {
            return $query;
        }

        return $query->where(function (Builder $query): void {
            $query->whereHas('metadataPrivate', function (Builder $query): void {
                $first = true;
                foreach ($this->value as $key => $value) {
                    if ($first) {
                        $query->where('name', 'LIKE', "%${key}%")
                            ->where('value', 'LIKE', "%${value}%");
                        $first = false;
                    } else {
                        $query->orWhere('name', 'LIKE', "%${key}%")
                            ->where('value', 'LIKE', "%${value}%");
                    }
                }
            });
        });
    }
}
