<?php

namespace App\DTO\Metadata;

use App\Enums\MetadataType;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Optional;

trait Metadata
{
    #[Computed]
    public Collection|Optional $metadata;

    private function mapMetadata(
        array|Optional $metadata = new Optional(),
        array|Optional $metadata_private = new Optional(),
        array|Optional $metadata_personal = new Optional(),
    ): void
    {
        $this->metadata = new Collection();

        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $this->metadata->push(
                    new MetadataDto(
                        $key,
                        $value,
                        true,
                        MetadataType::matchType($value)
                    ),
                );
            }
        }

        if (is_array($metadata_private)) {
            foreach ($metadata_private as $key => $value) {
                $this->metadata->push(
                    new MetadataDto(
                        $key,
                        $value,
                        true,
                        MetadataType::matchType($value)
                    ),
                );
            }
        }

        if (is_array($metadata_personal)) {
            foreach ($metadata_personal as $key => $value) {
                $this->metadata->push(
                    new MetadataDto(
                        $key,
                        $value,
                        true,
                        MetadataType::matchType($value)
                    ),
                );
            }
        }

        if ($this->metadata->isEmpty()) {
            $this->metadata = new Optional();
        }
    }
}
