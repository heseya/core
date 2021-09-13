<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Bootstrap the model and its traits.
     */
    public static function boot(): void
    {
        static::creating(function ($model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });

        parent::boot();
    }
}
