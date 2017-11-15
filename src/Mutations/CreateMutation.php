<?php

namespace Scrn\Bakery\Mutations;

use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Scrn\Bakery\Mutations\Concerns\SavesRelations;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CreateMutation
{
    use AuthorizesRequests, SavesRelations;

    /**
     * A reference to the model.
     */
    protected $model;

    /**
     * The class of the model. 
     *
     * @var string 
     */
    protected $class;

    /**
     * The name of the mutation.
     *
     * @var string 
     */
    public $name;

    /**
     * Construct a new create mutation.
     *
     * @param string $class
     * @param string $name
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->name = $this->formatName($class);
        $this->model = app()->make($class);
    }

    /**
     * Format the class name to the name for the create mutation.
     *
     * @param string $class
     * @return string
     */
    protected function formatName(string $class): string
    {
        return 'create' . title_case(str_singular(class_basename($class)));
    }

    /**
     * Get the attributes of the create mutation.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $input = 'Create' . title_case(str_singular(class_basename($this->class))) . 'Input';

        return [
            'name' => $this->name,
            'resolve' => [$this, 'resolve'],
            'type' => Bakery::getType(title_case(class_basename($this->class))),
            'args' => [
                'input' => Bakery::nonNull(Bakery::getType($input)),
            ]
        ];
    }

    /**
     * Convert the create mutation to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @return Model
     */
    public function resolve($root, $args = []): Model
    {
        $this->authorize('create', $this->class);

        $input = $args['input']; 
        $model = $this->model->make($this->getMassAssignableInput($input));

        $this->saveRelationsBefore($model, $input);
        $model->save();
        $this->saveRelationsAfter($model, $input);
        $this->insertRelations($model, $input);

        return $model;
    }

    /**
     * Get the input that is mass assignable by
     * cross referencing the input with the fields.
     *
     * @param array $args
     * @return array
     */
    protected function getMassAssignableInput(array $input): array
    {
        return array_filter($input, function ($key) {
            return in_array($key, array_keys($this->model->fields()));
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Save a has one relation.
     *
     * @param Model $model
     * @param Relations\HasOne $relation
     * @param string $value
     * @return void
     */
    protected function saveHasOneRelation(Model $model, Relations\HasOne $relation, $value)
    {
        $childModel = $relation->getQuery()->getModel();
        $child = $childModel->findOrFail($value);
        $child->{$relation->getForeignKeyName()} = $model->id;
        $child->save();
    }

    /**
     * Save a belongs to many relation. 
     *
     * @param Model $model
     * @param Relations\BelongsToMany $relation
     * @param string $value
     * @return void
     */
    protected function saveBelongsToManyRelation(Model $model, Relations\BelongsToMany $relation, $value)
    {
        $relation->attach($value);
    }

    /**
     * Save a belongs to relation.
     *
     * @param Model $model
     * @param Relations\BelongsTo $relation
     * @param string $value
     * @return void
     */
    protected function saveBelongsToRelation(Model $model, Relations\BelongsTo $relation, $value)
    {
        $model->{$relation->getForeignKey()} = $value;
    }

    /**
     * Insert a has many relation.
     *
     * @param Model $model
     * @param Relations\HasMany $relation
     * @param array $value
     * @return void
     */
    protected function insertHasManyRelation(Model $model, Relations\HasMany $relation, array $value)
    {
        $relation->createMany($value);
    }
}
