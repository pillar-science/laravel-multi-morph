# Laravel Multi Morph

Allows to use multiple MorphOne/MorphMany relationships with same model

### Problem

When adding multiple morph relationships to the same models, Laravel can't tell the difference between them. In the table related to `MyFile` we will have

When a model have multiple morph relationships with the same model, this cause a problem when fetching information on those relationships. Laravel only store the information on which model type (here `User`) the related model belongs to. As so, when fetching the `profilePicture` or `resume` relations, we get the same result with both files (assuming they exists)

```
class User extends Model
{
    public function profilePicture()
    {
        return $this->morphOne(MyFile::class, 'resource');
    }
    
    public function resume()
    {
        return $this->morphOne(MyFile::class, 'resource');
    }
}
```

```
class MyFile extends Model
{
    public function resource()
    {
        return $this->morphTo();
    }
}
```

### Solution

laravel-morph-multiple gives access to two more relationships `morphOneRelationship` and `morphManyRelationship` which will add the relation specific information in an additional column on the model with `morphToRelationship`

## How to use

You need to add a column to the **morphedTo Model** to store the relationship name it was stored for. 

### Migration
Here is an example migration for the **morphedTo Model**.
```
Schema::table('files', function (Blueprint $table) {
    $table->string('resource_relationship')->after('resource_type')->nullable();
});
```

### Replace methods within trait

We need to import the trait `HasMorphByRelationships` and use it on **both** side of the relation (**morphOneMany** and **morphTo**). You need to replace the existing relations with the news one from the trait

| Original    | Replace by              |
| ----------- | ----------------------- |
| `morphOne`  | `morphOneRelationship`  |
| `morphMany` | `morphManyRelationship` |
| `morphTo`   | `morphToRelationship`   |

### Code example

```
use PillarScience\LaravelMultiMorph\Eloquent\Concerns\HasMorphByRelationships;

/**
 * refered as MorphedOneMany Model
 */
class User extends Model
{
    use HasMorphByRelationships;

    public function profilePicture()
    {
        return $this->morphOneRelationship(MyFile::class, 'resource');
    }
    
    public function resume()
    {
        return $this->morphOneRelationship(MyFile::class, 'resource');
    }
}
```

```
use PillarScience\LaravelMultiMorph\Eloquent\Concerns\HasMorphByRelationships;

/**
 * refered as MorphedTo Model
 */
class MyFile extends Model
{
    use HasMorphByRelationships;

    public function resource()
    {
        return $this->morphTo();
    }
}
```

### Associating a model

```
// $user is a User model

$file = new MyFile();
$file->resource()->associate($user, 'resume');
```
