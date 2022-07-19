<?php

namespace App\Models;

use App\Traits\HasUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model as LaravelModel;
use Illuminate\Support\Carbon;

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

    protected function serializeDate(DateTimeInterface $date): string
    {
        // 2019-02-01T03:45:27+00:00
        return Carbon::instance($date)->toIso8601String();
    }
}
