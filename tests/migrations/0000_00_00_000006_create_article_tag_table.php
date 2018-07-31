<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreateArticleTagTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('article_tag', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('article_id');
            $table->unsignedInteger('tag_id');

            $table->foreign('article_id')
                ->references('id')->on('articles')
                ->onDelete('cascade');

            $table->foreign('tag_id')
                ->references('id')->on('tags')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::drop('article_tag');
    }
}
