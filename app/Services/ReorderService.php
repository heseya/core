<?php

namespace App\Services;

use App\Services\Contracts\ReorderServiceContract;

class ReorderService implements ReorderServiceContract
{
    public function reorder(array $array, $inObject = false): array
    {
        $return = [];

        foreach ($array as $key => $id) {
            if ($inObject) {
                $return[$key]['order'] = $key;
                continue;
            }

            $return[$id]['order'] = $key;
        }

        return $return;
    }
}
