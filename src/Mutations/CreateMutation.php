<?php

namespace Scrn\Bakery\Mutations;

use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CreateMutation
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
        return [
            'name' => $this->name,
            'resolve' => [$this, 'resolve'],
            'type' => Bakery::getType(title_case(class_basename($this->class))),
            'args' => [
                'input' => Bakery::getType($this->name . 'Input'),
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

        return $this->model->create($args['input']);
    }
}
