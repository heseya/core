<?php

declare(strict_types=1);

namespace Support\Utils;

use Domain\Metadata\Dtos\MetadataPersonalDto;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Metadata\Enums\MetadataType;
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
        $return = self::getMetadata($metadata, $metadata_private);

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

    public static function toUserMetadata(
        array|Optional $metadata = new Optional(),
        array|Optional $metadata_private = new Optional(),
        array|Optional $metadata_personal = new Optional(),
    ): array|Optional {
        $return = self::getMetadata($metadata, $metadata_private);

        if (is_array($metadata_personal)) {
            foreach ($metadata_personal as $key => $value) {
                $return[] = new MetadataPersonalDto(
                    $key,
                    $value,
                    MetadataType::matchType($value)
                );
            }
        }

        return count($return) > 0 ? $return : new Optional();
    }

    private static function getMetadata(array|Optional $metadata, array|Optional $metadata_private): array
    {
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

        return $return;
    }
}
