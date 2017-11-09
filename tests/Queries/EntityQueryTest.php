<?php

namespace Scrn\Bakery\Tests;

use Schema;
use Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Scrn\Bakery\Tests\Stubs\Model;
use Scrn\Bakery\Tests\WithDatabase;
use Scrn\Bakery\Queries\EntityQuery;

class EntityQueryTest extends TestCase
{
    use WithDatabase;

    /** @test */
    public function it_resolves_an_entity_by_its_primary_key()
    {
        Schema::create('models', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $model = Model::create();

        $query = new EntityQuery(Model::class, 'model');
        $result = $query->resolve(['id' => 1]);

        $this->assertTrue($model->is($result));
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

        $query = new EntityQuery(Model::class, 'model');
        $result = $query->resolve(['slug' => 'test-model']);

        $this->assertTrue($model->is($result));
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

        $query = new EntityQuery(Model::class, 'model');
        $result = $query->resolve(['slug' => 'test-model', 'category' => 'foo']);

        $this->assertTrue($model->is($result));
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

        $query = new EntityQuery(Model::class, 'model');
        $result = $query->resolve(['id' => 1]);
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

        $query = new EntityQuery(Model::class, 'model');
        $result = $query->resolve(['slug' => 'test-model', 'category' => 'foo']);
    }
}