<?php

namespace App\Traits;

use Domain\Metadata\Models\Metadata;
use Domain\Metadata\Models\MetadataPersonal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

trait MetadataResource
{
    public function metadataResource(?string $privateMetadataPermission = null): array
    {
        $data['metadata'] = $this->processMetadata($this->resource->metadata);

        if ($privateMetadataPermission !== null && Gate::allows($privateMetadataPermission)) {
            $data['metadata_private'] = $this->processMetadata($this->resource->metadataPrivate);
        }

        return $data;
    }

    /**
     * @param Collection<int, Metadata> $data
     */
    private function processMetadata(Collection $data): object
    {
        // Special workaround for frond-end requirements
        if ($data->count() <= 0) {
            return (object) [];
        }

        return $data->mapWithKeys(fn (Metadata|MetadataPersonal $meta) => [$meta->name => $meta->value]);
    }
}
