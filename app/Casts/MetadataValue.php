<?php

namespace App\Casts;

use App\Enums\MetadataType;
use App\Models\Metadata;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MetadataValue implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Metadata $model
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return match ($model->value_type->value) {
            MetadataType::BOOLEAN => (bool) $value,
            MetadataType::NUMBER => (float) $value,
            default => $value
        };
    }

    public function set(Model $model, string $key, $value, array $attributes): mixed
    {
        return $value;
    }
}
