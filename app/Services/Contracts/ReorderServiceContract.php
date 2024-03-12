<?php

namespace App\Services\Contracts;

use App\DTO\ReorderDto;
use App\Models\Model;

interface ReorderServiceContract
{
    public function reorder(array $array): array;

    /**
     * @param class-string<Model> $class
     */
    public function reorderAndSave(string $class, ReorderDto $dto): void;

    /**
     * @param class-string<Model> $class
     */
    public function assignOrderToAll(string $class): void;
}
