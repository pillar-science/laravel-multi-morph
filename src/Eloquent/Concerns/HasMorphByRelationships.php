<?php

namespace PillarScience\LaravelMultiMorph\Eloquent\Concerns;

use PillarScience\LaravelMultiMorph\Eloquent\Relations\MorphManyRelationship;
use PillarScience\LaravelMultiMorph\Eloquent\Relations\MorphOneRelationship;
use PillarScience\LaravelMultiMorph\Eloquent\Relations\MorphToRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasMorphByRelationships
{
    /**
     * Get the polymorphic relationship columns.
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @return array
     */
    protected function getMorphs($name, $type, $id)
    {
        return [$type ?: $name.'_type', $id ?: $name.'_id', $name.'_relationship'];
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $localKey
     * @return MorphManyRelationship
     */
    public function morphManyRelationship($related, $name, $relationshipName = null, $type = null, $id = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
        [$type, $id, $relationshipColumn] = $this->getMorphs($name, $type, $id);

        $relationshipName = $relationshipName ?: $this->guessBelongsToRelation();

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphManyRelationship($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $table.'.'.$relationshipColumn, $relationshipName);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $localKey
     * @return MorphOneRelationship
     */
    public function morphOneRelationship($related, $name, $relationshipName = null, $type = null, $id = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        [$type, $id, $relationshipColumn] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $relationshipName = $relationshipName ?: $this->guessBelongsToRelation();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphOneRelationship($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $table.'.'.$relationshipColumn, $relationshipName);
    }

    /**
     * Instantiate a new MorphMany relationship.
     * Overrides the method from \Illuminate\Database\Eloquent\Concerns\HasRelationships::newMorphMany
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return MorphManyRelationship
     */
    protected function newMorphManyRelationship(Builder $query, Model $parent, $type, $id, $localKey, $morphRelationship, $relationshipName)
    {
        return new MorphManyRelationship($query, $parent, $type, $id, $localKey, $morphRelationship, $relationshipName);
    }

    /**
     * Instantiate a new MorphOne relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return MorphOneRelationship
     */
    protected function newMorphOneRelationship(Builder $query, Model $parent, $type, $id, $localKey, $morphRelationship, $relationshipName)
    {
        return new MorphOneRelationship($query, $parent, $type, $id, $localKey, $morphRelationship, $relationshipName);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string|null  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $ownerKey
     * @return MorphToRelationship
     */
    public function morphToRelationship($name = null, $type = null, $id = null, $ownerKey = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        $name = $name ?: $this->guessBelongsToRelation();

        [$type, $id, $relationDiscriminant] = $this->getMorphs(
            Str::snake($name), $type, $id
        );

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. In this case we'll just pass in a dummy query where we
        // need to remove any eager loads that may already be defined on a model.
        return empty($class = $this->{$type})
            ? $this->morphEagerToRelationship($name, $type, $id, $ownerKey, $relationDiscriminant)
            : $this->morphInstanceToRelationship($class, $name, $type, $id, $ownerKey, $relationDiscriminant);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $ownerKey
     * @return MorphToRelationship
     */
    protected function morphEagerToRelationship($name, $type, $id, $ownerKey, $relationDiscriminant)
    {
        return $this->newMorphToRelationship(
            $this->newQuery()->setEagerLoads([]), $this, $id, $ownerKey, $type, $name, $relationDiscriminant
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string  $target
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $ownerKey
     * @return MorphToRelationship
     */
    protected function morphInstanceToRelationship($target, $name, $type, $id, $ownerKey, $relationDiscriminant)
    {
        $instance = $this->newRelatedInstance(
            static::getActualClassNameForMorph($target)
        );

        return $this->newMorphToRelationship(
            $instance->newQuery(), $this, $id, $ownerKey ?? $instance->getKeyName(), $type, $name, $relationDiscriminant
        );
    }

    /**
     * Instantiate a new MorphTo relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $type
     * @param  string  $relation
     * @return MorphToRelationship
     */
    protected function newMorphToRelationship(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation, $relationDiscriminant)
    {
        return new MorphToRelationship($query, $parent, $foreignKey, $ownerKey, $type, $relation, $relationDiscriminant);
    }
}
