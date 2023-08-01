<?php

namespace App\Traits;

use App\Enums\MetadataType;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Metadata\Dtos\MetadataPersonalDto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;

trait MapMetadata
{
    public static function mapMetadata(Request $request): array|Missing
    {
        // mapping form data
        $data = [];
        foreach ($request->input() as $key => $value) {
            data_set($data, $key, $value);
        }

        return self::mapMetadataFromArray($data);
    }

    public static function mapMetadataFromArray(array $data): array|Missing
    {
        $metadata = [];

        if (array_key_exists('metadata', $data)) {
            foreach ($data['metadata'] as $key => $value) {
                $metadata[] = new MetadataUpdateDto(
                    $key,
                    $value,
                    true,
                    MetadataType::matchType($value),
                );
            }
        }

        if (array_key_exists('metadata_private', $data)) {
            foreach ($data['metadata_private'] as $key => $value) {
                $metadata[] = new MetadataUpdateDto(
                    $key,
                    $value,
                    false,
                    MetadataType::matchType($value),
                );
            }
        }

        if (array_key_exists('metadata_personal', $data)) {
            foreach ($data['metadata_personal'] as $key => $value) {
                $metadata[] = new MetadataPersonalDto(
                    $key,
                    $value,
                    MetadataType::matchType($value),
                );
            }
        }

        return count($metadata) > 0 ? $metadata : new Missing();
    }

    public function getMetadata(): array|Missing
    {
        return $this->metadata;
    }
}
