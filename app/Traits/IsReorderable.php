<?php

namespace App\Traits;

use App\Models\Model;

trait IsReorderable
{
    public static function bootIsReorderableTrait(): void
    {
        // @param IsReorderable $model
        static::saving(function (Model $model): void {
            if (empty($model->{$this->getOrderColumnName()})) {
                $model->setHighestOrderNumber();
            }
        });
    }

    public function getOrderColumnName(): string
    {
        return 'order';
    }

    public function setHighestOrderNumber(): void
    {
        $orderColumnName = $this->getOrderColumnName();

        $this->{$orderColumnName} = $this->getHighestOrderNumber() + 1;
    }

    public function getHighestOrderNumber(): int
    {
        return static::query()->max($this->getOrderColumnName()) ?? 0;
    }
}
