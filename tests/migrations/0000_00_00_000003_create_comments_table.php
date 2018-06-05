<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->text('body');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('article_id');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('article_id')
                ->references('id')->on('articles')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::drop('comments');
    }
}
