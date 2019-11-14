<?php

namespace Bakery\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ModelSchemaCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'bakery:modelschema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model schema class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'ModelSchema';

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    public function buildClass($name)
    {
        $model = $this->option('model');

        if (is_null($model)) {
            $model = $this->rootNamespace().$this->argument('name');
        } elseif (! Str::startsWith($model, [$this->rootNamespace(), '\\'])) {
            $model = $this->rootNamespace().$model;
        }

        return str_replace('DummyFullModel', $model, parent::buildClass($name));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/modelschema.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Bakery';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_REQUIRED, 'The model class being represented.'],
        ];
    }
}
