<?php

namespace App\Traits;

use App\DTO\Metadata\MetadataDto;
use App\DTO\Metadata\MetadataPersonalDto;
use App\Enums\MetadataType;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait MapMetadata
{
    public static function mapMetadata(Request $request): array|Missing
    {
        // mapping form data
        $data = [];
        foreach ($request->input() as $key => $value) {
            data_set($data, $key, $value);
        }

        $metadata = Collection::make();
        if (array_key_exists('metadata', $data)) {
            foreach ($data['metadata'] as $key => $value) {
                $metadata->push(new MetadataDto($key, $value, true, MetadataType::matchType($value)));
            }
        }

        if ($request->has('metadata_private')) {
            foreach ($request->input('metadata_private') as $key => $value) {
                $metadata->push(new MetadataDto($key, $value, false, MetadataType::matchType($value)));
            }
        }

        if ($request->has('metadata_personal')) {
            foreach ($request->input('metadata_personal') as $key => $value) {
                $metadata->push(new MetadataPersonalDto($key, $value, MetadataType::matchType($value)));
            }
        }

        return $metadata->isEmpty() ? new Missing() : $metadata->toArray();
    }

    public static function mapMetadataFromArray(array $data): array|Missing
    {
        $metadata = Collection::make();
        if (array_key_exists('metadata', $data)) {
            foreach ($data['metadata'] as $key => $value) {
                $metadata->push(new MetadataDto($key, $value, true, MetadataType::matchType($value)));
            }
        }

        if (array_key_exists('metadata_private', $data)) {
            foreach ($data['metadata_private'] as $key => $value) {
                $metadata->push(new MetadataDto($key, $value, false, MetadataType::matchType($value)));
            }
        }

        return $metadata->isEmpty() ? new Missing() : $metadata->toArray();
    }

    public function getMetadata(): array|Missing
    {
        return $this->metadata;
    }
}
