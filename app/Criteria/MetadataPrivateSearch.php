<?php

namespace App\Criteria;

use App\Traits\PermissionUtility;
use Illuminate\Database\Eloquent\Builder;

class MetadataPrivateSearch extends MetadataSearch
{
    use PermissionUtility;

    public function query(Builder $query): Builder
    {
        if ($this->deniesAbilityByModel('show_metadata_private', $query->getModel())) {
            return $query;
        }

        return $this->makeQuery($query, 'metadataPrivate');
    }
}
