<?php

namespace Bakery\Tests;

use Schema;
use Bakery\Tests\Stubs\User;

trait WithDatabase
{
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
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name');
            $table->string('password');
            $table->string('secret_information')->nullable();
            $table->timestamps();
        });

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->string('lower_case')->nullable();
            $table->integer('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function ($table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('comments', function ($table) {
            $table->increments('id');
            $table->string('body')->nullable();
            $table->integer('post_id');
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('phones', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('number')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('role_user', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('role_id')->nullable();
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
        $user->email = 'j.doe@example.com';
        $user->password = 'secret';
        $user->save();

        return $user;
    }
}
