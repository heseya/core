<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model as LaravelModel;

abstract class Model extends LaravelModel
{
    use HasUuid;

    protected array $forceAudit = [];

    public function forceAudit(string ...$attributes): void
    {
        $this->forceAudit = array_merge($this->forceAudit, $attributes);
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (in_array($key, $this->forceAudit) || !$this->originalIsEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }
}
