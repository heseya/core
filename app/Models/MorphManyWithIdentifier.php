<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MorphManyWithIdentifier extends MorphMany
{
    public function __construct(
        Builder $query,
        Model $parent,
        $type,
        $id,
        $localKey,
        protected readonly string $identifierName,
        protected readonly string $identifier,
    ) {
        parent::__construct($query, $parent, $type, $id, $localKey);
    }

    protected function setForeignAttributesForCreate(Model $model): void
    {
        parent::setForeignAttributesForCreate($model);

        $model->{$this->identifierName} = $this->identifier;
    }

    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->getRelationQuery()->where($this->identifierName, $this->identifier);

            parent::addConstraints();
        }
    }

    public function addEagerConstraints(array $models): void
    {
        parent::addEagerConstraints($models);

        $this->getRelationQuery()->where($this->identifierName, $this->identifier);
    }
}
