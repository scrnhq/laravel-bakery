<?php

namespace Bakery\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bakery:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all Bakery resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Bakery Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'bakery-config']);

        $this->comment('Generating User Schema...');
        $this->callSilent('bakery:modelschema', ['name' => 'User']);
        copy(__DIR__.'/stubs/user-schema.stub', app_path('Bakery/User.php'));

        $this->setAppNamespace(app_path('Bakery/User.php'), $this->laravel->getNamespace());
    }

    /**
     * Set the namespace on the given file.
     *
     * @param  string  $file
     * @param  string  $namespace
     * @return void
     */
    public function setAppNamespace($file, $namespace)
    {
        file_put_contents($file, str_replace('App\\', $namespace, file_get_contents($file)));
    }
}
