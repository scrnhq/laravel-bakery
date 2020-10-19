<?php

namespace Bakery\Mutations;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Bakery\Traits\FiltersQueries;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;

class UpdateManyMutation extends Mutation
{
    use FiltersQueries;

    /** @var \Bakery\Eloquent\ModelSchema */
    protected $modelSchema;

    /**
     * EloquentMutation constructor.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     */
    public function __construct(TypeRegistry $registry, ModelSchema $modelSchema = null)
    {
        parent::__construct($registry);

        if ($modelSchema) {
            $this->modelSchema = $modelSchema;
        } elseif (is_string($this->modelSchema)) {
            $this->modelSchema = $this->registry->getModelSchema($this->modelSchema);
        }

        Utils::invariant(
            $this->modelSchema instanceof ModelSchema,
            'Model schema on '.get_class($this).' should be an instance of '.ModelSchema::class
        );
    }

    /**
     * Get the name of the mutation.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return 'updateMany'.$this->modelSchema->pluralTypename();
    }

    /**
     * The return type of the mutation.
     *
     * @return Type
     */
    public function type(): Type
    {
        return $this->registry->int();
    }

    /**
     * The arguments of the mutation.
     *
     * @return array
     */
    public function args(): array
    {
        $inputTypeName = 'Update'.$this->modelSchema->typename().'Input';

        return [
            'filter' => $this->registry->type($this->modelSchema->typename().'Filter'),
            'input' => $this->registry->type($inputTypeName),
        ];
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed  $root
     * @param  mixed  $args
     * @param  mixed  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $info
     * @return int
     */
    public function resolve($root, array $args, $context, ResolveInfo $info)
    {
        $input = $args['input'];

        $query = $this->modelSchema->getQuery();

        $this->applyFilters($query, $args['filter']);

        $count = $query->count();

        DB::transaction(function () use ($query, $input) {
            $query->each(function (Model $model) use ($input) {
                $modelSchema = $this->registry->getSchemaForModel($model);

                return $modelSchema->updateIfAuthorized($input);
            });
        });

        return $count;
    }
}
