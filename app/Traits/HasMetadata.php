<?php

namespace App\Traits;

use App\Models\Metadata;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMetadata
{
    public function metadata(): MorphMany
    {
        return $this
            ->morphMany(Metadata::class, 'model', 'model_type', 'model_id')
            ->public();
    }

    public function metadataPrivate(): MorphMany
    {
        return $this
            ->morphMany(Metadata::class, 'model', 'model_type', 'model_id')
            ->private();
    }
}
