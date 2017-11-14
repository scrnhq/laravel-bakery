<?php

namespace Scrn\Bakery\Tests;

use Schema;
use Scrn\Bakery\Tests\Stubs\User;

trait WithDatabase {
    /**
     * Set up a test database. 
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function setupDatabase($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Migrate the database.
     *
     * @return void
     */
    protected function migrateDatabase()
    {
        $this->loadLaravelMigrations([]);

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->integer('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Create a new user.
     *
     * @return User
     */
    protected function createUser(): User
    {
        $user = new User();
        $user->name = 'John Doe';
        $user->email = '.j.doe@example.com';
        $user->password = 'secret';
        return $user;
    }
}
