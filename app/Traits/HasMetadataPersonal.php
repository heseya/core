<?php

namespace App\Traits;

use App\Models\MetadataPersonal;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMetadataPersonal
{
    public function metadataPersonal(): MorphMany
    {
        return $this->morphMany(MetadataPersonal::class, 'model', 'model_type', 'model_id');
    }
}
