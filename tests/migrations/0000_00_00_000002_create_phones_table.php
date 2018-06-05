<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhonesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('phones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('number')->unique();
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::drop('phones');
    }
}
