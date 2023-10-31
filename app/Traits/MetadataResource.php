<?php

namespace App\Traits;

use App\Models\Model;
use Domain\Metadata\Models\Metadata;
use Domain\Metadata\Models\MetadataPersonal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

trait MetadataResource
{
    public function metadataResource(?string $privateMetadataPermission = null, ?Model $overrideResource = null): array
    {
        $metadata = $overrideResource?->metadata ?? $this->resource->metadata ?? null;

        $data['metadata'] = empty($metadata) ? (object) [] : $this->processMetadata($metadata);

        if ($privateMetadataPermission !== null && Gate::allows($privateMetadataPermission)) {
            $metadataPrivate = $overrideResource?->metadataPrivate ?? $this->resource->metadataPrivate ?? null;

            if (!empty($metadataPrivate)) {
                $data['metadata_private'] = $this->processMetadata($metadataPrivate);
            }
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
