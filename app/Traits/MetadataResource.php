<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

trait MetadataResource
{
    public function metadataResource(?string $privateMetadataPermission = null): array
    {
        $data['metadata'] = $this->processMetadata($this->metadata);

        if ($privateMetadataPermission !== null && Gate::allows($privateMetadataPermission)) {
            $data['metadata_private'] = $this->processMetadata($this->metadataPrivate);
        }

        return $data;
    }

    private function processMetadata(Collection $data)
    {
        /**
         * Special workaround for frond-end requirements
         * */
        if ($data->count() <= 0) {
            return (object) [];
        }

        return $data->mapWithKeys(fn ($meta) => [$meta->name => $meta->value]);
    }
}
