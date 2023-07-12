<?php

declare(strict_types=1);

namespace App\Utils;

use App\DTO\Metadata\MetadataDto;
use App\Enums\MetadataType;
use Spatie\LaravelData\Optional;

final readonly class Map
{
    public static function toMetadata(
        array|Optional $metadata = new Optional(),
        array|Optional $metadata_private = new Optional(),
    ): array|Optional {
        $return = [];

        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $return[] = new MetadataDto(
                    $key,
                    $value,
                    true,
                    MetadataType::matchType($value)
                );
            }
        }

        if (is_array($metadata_private)) {
            foreach ($metadata_private as $key => $value) {
                $return[] = new MetadataDto(
                    $key,
                    $value,
                    false,
                    MetadataType::matchType($value)
                );
            }
        }

        return count($return) > 0 ? $return : new Optional();
    }
}
