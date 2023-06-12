<?php

namespace App\Models;

use App\Traits\HasUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
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

    // format for database
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        // 2019-02-01T03:45:27+00:00
        return Carbon::instance($date)->toIso8601String();
    }

    public function morphOneWithIdentifier(
        string $related,
        string $name,
        string $identifierName,
        string $identifier,
        ?string $type = null,
        ?string $id = null,
        ?string $localKey = null,
    ): MorphOneWithIdentifier {
        $instance = $this->newRelatedInstance($related);

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphOneWithIdentifier(
            $instance->newQuery(),
            $this,
            $table . '.' . $type,
            $table . '.' . $id,
            $localKey,
            $identifierName,
            $identifier,
        );
    }

    protected function newMorphOneWithIdentifier(
        Builder $query,
        LaravelModel $parent,
        string $type,
        string $id,
        string $localKey,
        string $identifierName,
        string $identifier,
    ): MorphOneWithIdentifier {
        return new MorphOneWithIdentifier(
            $query,
            $parent,
            $type,
            $id,
            $localKey,
            $identifierName,
            $identifier,
        );
    }
}
