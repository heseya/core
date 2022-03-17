<?php

namespace App\Traits;

use Illuminate\Support\Facades\Gate;

trait MetadataResource
{
    public function metadataResource(?string $prefix = null): array
    {
        $data['metadata'] = [];

        foreach ($this->metadata as $metadata) {
            $data['metadata'][$metadata->name] = $metadata->value;
        }

        if ($prefix !== null && Gate::allows("{$prefix}.show_metadata_private")) {
            $data['metadata_private'] = [];

            foreach ($this->metadataPrivate as $metadataPrivate) {
                $data['metadata_private'][$metadataPrivate->name] = $metadataPrivate->value;
            }
        }

        return $data;
    }
}
