<?php

namespace App\Traits;

use App\Models\Metadata;

trait HasMetadata
{
    public function metadata()
    {
        return $this
            ->morphMany(Metadata::class, 'model', 'model_type', 'model_id')
            ->public();
    }

    public function metadata_private()
    {
        return $this
            ->morphMany(Metadata::class, 'model', 'model_type', 'model_id')
            ->private();
    }
}
