<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreateUpvotesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('upvotes', function ($table) {
            $table->increments('id');
            $table->morphs('upvoteable');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('upvotes');
    }
}
