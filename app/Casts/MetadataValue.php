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
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     *
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes): mixed
    {
        return match ($model->value_type) {
            MetadataType::BOOLEAN => (bool) $value,
            MetadataType::NUMBER => (float) $value,
            default => $value
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     *
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes): mixed
    {
        return $value;
    }
}
