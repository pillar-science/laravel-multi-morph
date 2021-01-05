<?php

namespace PillarScience\LaravelMultiMorph\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MorphOneRelationship extends MorphOne
{
    /**
     * The relationship column name to use
     *
     * @var string
     */
    protected $morphRelationship;

    /**
     * The relationship value to use
     *
     * @var string
     */
    protected $relationshipName;

    public function __construct(Builder $query, Model $parent, $type, $id, $localKey, $morphRelationship, $relationshipName)
    {
        $this->morphRelationship = $morphRelationship;

        $this->relationshipName = $relationshipName;

        parent::__construct($query, $parent, $type, $id, $localKey);
    }

    protected function setForeignAttributesForCreate(Model $model)
    {
        $model->{$this->getMorphRelationship()} = $this->relationshipName;

        parent::setForeignAttributesForCreate($model);
    }

    protected function getMorphRelationship()
    {
        return $this->morphRelationship;
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            parent::addConstraints();

            $this->query->where($this->morphRelationship, $this->relationshipName);
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

        $this->query->where($this->morphRelationship, $this->relationshipName);
    }
}
