<?php

declare(strict_types=1);

namespace Support\Utils;

use App\Enums\MetadataType;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Metadata\Dtos\MetadataPersonalDto;
use Spatie\LaravelData\Optional;

final readonly class Map
{
    /**
     * @param string[]|Optional $metadata
     * @param string[]|Optional $metadata_private
     *
     * @return MetadataUpdateDto[]|Optional
     */
    public static function toMetadata(
        array|Optional $metadata = new Optional(),
        array|Optional $metadata_private = new Optional(),
    ): array|Optional {
        $return = [];

        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $return[] = new MetadataUpdateDto(
                    $key,
                    $value,
                    true,
                    MetadataType::matchType($value)
                );
            }
        }

        if (is_array($metadata_private)) {
            foreach ($metadata_private as $key => $value) {
                $return[] = new MetadataUpdateDto(
                    $key,
                    $value,
                    false,
                    MetadataType::matchType($value)
                );
            }
        }

        return count($return) > 0 ? $return : new Optional();
    }

    /**
     * @param string[]|Optional $metadata
     *
     * @return MetadataPersonalDto[]|Optional
     */
    public static function toMetadataPersonal(
        array|Optional $metadata = new Optional(),
    ): array|Optional {
        $return = [];

        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $return[] = new MetadataPersonalDto(
                    $key,
                    $value,
                    MetadataType::matchType($value)
                );
            }
        }

        return count($return) > 0 ? $return : new Optional();
    }
}
