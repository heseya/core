<?php

declare(strict_types=1);

namespace Domain\Metadata\Casts;

use App\Enums\MetadataType;
use App\Models\Metadata;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @phpstan-ignore-next-line */
final readonly class MetadataValue implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Metadata $model
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): bool|float|string
    {
        return match ($model->value_type) {
            MetadataType::BOOLEAN => (bool) $value,
            MetadataType::NUMBER => (float) $value,
            default => (string) $value,
        };
    }

    /**
     * @param Metadata $model
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
