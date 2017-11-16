<?php

namespace Scrn\Bakery\Mutations;

use Scrn\Bakery\Support\Field;
use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Scrn\Bakery\Exceptions\TooManyResultsException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DeleteMutation extends Field
{
    use AuthorizesRequests;

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
     * Construct a new update mutation.
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
     * Format the class name to the name for the update mutation.
     *
     * @param string $class
     * @return string
     */
    protected function formatName(string $class): string
    {
        return 'delete' . title_case(str_singular(class_basename($class)));
    }

    /**
     * Get the return type of the mutation.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::boolean();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args()
    {
        return array_merge([
            $this->model->getKeyName() => Type::ID(),
        ], $this->model->lookupFields());
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @return bool
     */
    public function resolve($root, $args = []): bool
    {
        $model = $this->getModel($args);
        $this->authorize('delete', $model);

        return $model->delete();
    }

    /**
     * Get the model for the mutation.
     *
     * @param array $args
     * @return Model
     */
    protected function getModel(array $args): Model
    {
        $primaryKey = $this->model->getKeyName();
        
        if (array_key_exists($primaryKey, $args)) {
            return $this->model->findOrFail($args[$primaryKey]);
        }

        $query = $this->model->query();

        foreach($args as $key => $value) {
            $query->where($key, $value);
        }

        $results = $query->get();

        if ($results->count() < 1) {
            throw (new ModelNotFoundException)->setModel($this->class);
        }
        
        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel($this->class, $results->pluck($this->model->getKeyName()));
        }

        return $results->first();
    }
}
