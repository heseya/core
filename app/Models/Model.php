<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model as LaravelModel;
use Support\Models\HasNormalizedDates;

abstract class Model extends LaravelModel
{
    use HasNormalizedDates;
    use HasUuid;

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (!$this->originalIsEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }
}
