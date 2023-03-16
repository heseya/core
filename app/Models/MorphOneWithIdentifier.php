<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MorphOneWithIdentifier extends MorphOne
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

    protected function setForeignAttributesForCreate(Model $model)
    {
        parent::setForeignAttributesForCreate($model);

        $model->{$this->identifierName} = $this->identifier;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->getRelationQuery()->where($this->identifierName, $this->identifier);

            parent::addConstraints();
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->getRelationQuery()->where($this->identifierName, $this->identifier);
    }
}
