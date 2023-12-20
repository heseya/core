<?php

namespace App\Services;

use App\DTO\ReorderDto;
use App\Models\Model;
use App\Services\Contracts\ReorderServiceContract;
use App\Traits\IsReorderable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ReorderService implements ReorderServiceContract
{
    public function reorder(array $array): array
    {
        $return = [];

        foreach ($array as $key => $id) {
            $return[$id]['order'] = $key;
        }

        return $return;
    }

    /**
     * @param class-string<Model> $class
     */
    public function reorderAndSave(string $class, ReorderDto $dto): void
    {
        $model = new $class();

        if (in_array(IsReorderable::class, class_uses_recursive($class)) && method_exists($model, 'getOrderColumnName')) {
            /** @var string $orderColumnName */
            $orderColumnName = $model->getOrderColumnName();
        } else {
            $orderColumnName = 'order';
        }

        foreach ($dto->ids as $key => $id) {
            $model::query()
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->where($model->getKeyName(), $id)
                ->update([$orderColumnName => $key]);
        }
    }

    /**
     * @param class-string<Model> $class
     */
    public function assignOrderToAll(string $class): void
    {
        $model = new $class();

        if (in_array(IsReorderable::class, class_uses_recursive($class)) && method_exists($model, 'getOrderColumnName') && method_exists($model, 'getHighestOrderNumber')) {
            /** @var string $orderColumnName */
            $orderColumnName = $model->getOrderColumnName();
            /** @var int $order */
            $order = $model->getHighestOrderNumber();
        } else {
            $orderColumnName = 'order';
            $order = $model::query()->max($orderColumnName) ?? 0;
        }

        $model::query()
            ->getQuery()
            ->whereNull($orderColumnName)
            ->orderBy(
                match (true) {
                    $model->getKeyType() === 'int' => $model->getKeyName(),
                    $model->usesTimestamps() => $model->getUpdatedAtColumn() ?? $model->getCreatedAtColumn() ?? $model->getKeyName(),
                    default => $model->getKeyName(),
                },
                'asc',
            )
            ->chunkById(100, function (Collection $models) use ($orderColumnName, &$order): void {
                foreach ($models as $model) {
                    DB::table($model->getTable())
                        ->where($model->getKeyName(), $model->getKey())
                        ->update([$orderColumnName => ++$order]);
                }
            });
    }
}
