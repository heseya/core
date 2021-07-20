<?php

namespace App\Services;

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
}
