<?php

namespace App\Services\Contracts;

interface ReorderServiceContract
{
    public function reorder(array $array, $inObject = false): array;
}
