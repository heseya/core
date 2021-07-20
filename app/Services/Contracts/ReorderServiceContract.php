<?php

namespace App\Services\Contracts;

interface ReorderServiceContract
{
    public function reorder(array $array): array;
}
