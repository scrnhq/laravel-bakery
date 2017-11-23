<?php

namespace Bakery\Tests;

use Schema;
use Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Bakery\Tests\Stubs\Model;
use Bakery\Tests\WithDatabase;
use Bakery\Queries\SingleEntityQuery;

class SingleEntityQueryTest extends TestCase
{
    use WithDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $this->setupDatabase($app);
    }

    /** @test */
    public function it_resolves_an_entity_by_its_primary_key()
    {
        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $model = Model::create();

        $query = new SingleEntityQuery(Model::class);
        $result = $query->resolve(null, ['id' => 1], null);

        $this->assertEquals($model->id, $result['id']);
    }

    /** @test */
    public function it_resolves_an_entity_by_a_single_column()
    {
        Eloquent::unguard();

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('slug');
            $table->timestamps();
        });

        $model = Model::create(['slug' => 'test-model']);

        $query = new SingleEntityQuery(Model::class);
        $result = $query->resolve(null, ['slug' => 'test-model'], null);

        $this->assertEquals($model->id, $result['id']);
    }

    /** @test */
    public function it_resolves_an_entity_by_multiple_columns()
    {
        Eloquent::unguard();

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('category');
            $table->timestamps();
        });

        $model = Model::create(['slug' => 'test-model', 'category' => 'foo']);

        $query = new SingleEntityQuery(Model::class);
        $result = $query->resolve(null, ['slug' => 'test-model', 'category' => 'foo'], null);

        $this->assertEquals($model->id, $result['id']);
    }

    /** @test */
    public function it_throws_model_not_found_exception_when_model_with_primary_key_cannot_be_found()
    {
        $this->expectException(ModelNotFoundException::class);

        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        Model::create(['id' => '2']);

        $query = new SingleEntityQuery(Model::class);
        $result = $query->resolve(null, ['id' => 1], null);
    }

    /** @test */
    public function it_throws_model_not_found_exception_when_model_with_provided_columns_cannot_be_found()
    {
        $this->expectException(ModelNotFoundException::class);

        Eloquent::unguard();
        
        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('category');
            $table->timestamps();
        });

        $model = Model::create(['slug' => 'test-model', 'category' => 'bar']);

        $query = new SingleEntityQuery(Model::class);
        $result = $query->resolve(null, ['slug' => 'test-model', 'category' => 'foo'], null);
    }
}
