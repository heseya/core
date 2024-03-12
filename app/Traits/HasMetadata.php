<?php

namespace App\Traits;

use Domain\Metadata\Models\Metadata;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMetadata
{
    public function metadata(): MorphMany
    {
        return $this
            ->morphMany(Metadata::class, 'model', 'model_type', 'model_id')
            ->where('public', '=', true);
    }

    public function metadataPrivate(): MorphMany
    {
        return $this
            ->morphMany(Metadata::class, 'model', 'model_type', 'model_id')
            ->where('public', '=', false);
    }
}
