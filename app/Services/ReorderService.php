<?php

namespace App\Services;

use App\DTO\ReorderDto;
use App\Models\Model;
use App\Services\Contracts\ReorderServiceContract;

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
        foreach ($dto->ids as $key => $id) {
            $class::query()->where('id', $id)->update(['order' => $key]);
        }
    }
}
