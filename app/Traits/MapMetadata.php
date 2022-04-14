<?php

namespace App\Traits;

use App\Dtos\MetadataDto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait MapMetadata
{
    public static function mapMetadata(Request $request): array|Missing
    {
        $metadata = Collection::make();
        if ($request->has('metadata')) {
            foreach ($request->input('metadata') as $key => $value) {
                $metadata->push(MetadataDto::manualInit($key, $value, true));
            }
        }

        if ($request->has('metadata_private')) {
            foreach ($request->input('metadata_private') as $key => $value) {
                $metadata->push(MetadataDto::manualInit($key, $value, false));
            }
        }

        return $metadata->isEmpty() ? new Missing() : $metadata->toArray();
    }

    public static function mapMetadataFromArray(array $data): array|Missing
    {
        $metadata = Collection::make();
        if (array_key_exists('metadata', $data)) {
            foreach ($data['metadata'] as $key => $value) {
                $metadata->push(MetadataDto::manualInit($key, $value, true));
            }
        }

        if (array_key_exists('metadata_private', $data)) {
            foreach ($data['metadata_private'] as $key => $value) {
                $metadata->push(MetadataDto::manualInit($key, $value, false));
            }
        }

        return $metadata->isEmpty() ? new Missing() : $metadata->toArray();
    }

    public function getMetadata(): Missing|array
    {
        return $this->metadata;
    }
}
