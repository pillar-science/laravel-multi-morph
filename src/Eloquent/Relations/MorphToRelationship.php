<?php

namespace PillarScience\LaravelMultiMorph\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MorphToRelationship extends MorphTo
{
    protected $relationDiscriminant;

    public function __construct(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation, $relationDiscriminant)
    {
        $this->relationDiscriminant = $relationDiscriminant;

        parent::__construct($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model, $foreignRelationName = null)
    {
        if ($foreignRelationName) {
            $this->parent->setAttribute(
                sprintf('%s_relationship', $this->relationName), $foreignRelationName
            );
        }

        return parent::associate($model);
    }
}
